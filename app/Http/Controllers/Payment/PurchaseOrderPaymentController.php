<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Builder;

use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\General;

use App\Services\PaymentTransactionService;
use App\Services\AccountTransactionService;

use App\Http\Controllers\Purchase\PurchaseController;
use App\Models\Purchase\PurchaseOrder;
use App\Models\PaymentTransaction;
use App\Models\Purchase\Purchase;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Mpdf\Mpdf;

class PurchaseOrderPaymentController extends Controller
{
    use FormatNumber;

    use FormatsDateInputs;

    private $paymentTransactionService;
    private $accountTransactionService;

    public function __construct(
                                PaymentTransactionService $paymentTransactionService, 
                                AccountTransactionService $accountTransactionService
                            )
    {
        $this->paymentTransactionService = $paymentTransactionService;
        $this->accountTransactionService = $accountTransactionService;
    }


    public function deletePurchaseOrderPayment($paymentId) : JsonResponse{
        try {
            DB::beginTransaction();
            $paymentTransaction = PaymentTransaction::find($paymentId);
            if(!$paymentTransaction){
                throw new \Exception(__('payment.failed_to_delete_payment_transactions'));
            }

            //Purchase model id
            $purchaseId = $paymentTransaction->transaction_id;

            // Find the related account transaction
            $accountTransactions = $paymentTransaction->accountTransaction;
            if ($accountTransactions->isNotEmpty()) {
                foreach ($accountTransactions as $accountTransaction) {
                    $accountId = $accountTransaction->account_id;
                    // Do something with the individual accountTransaction
                    $accountTransaction->delete(); // Or any other operation
                    //Update  account
                    $this->accountTransactionService->calculateAccounts($accountId);
                }
            }

            $paymentTransaction->delete();

            /**
             * Update Purchase Model
             * Total Paid Amunt
             * */
            $purchase = PurchaseOrder::find($purchaseId);
            if(!$this->paymentTransactionService->updateTotalPaidAmountInModel($purchase)){
                throw new \Exception(__('payment.failed_to_update_paid_amount'));
            }

            DB::commit();
            return response()->json([
                'status'    => true,
                'message' => __('app.record_deleted_successfully'),
                'data'  => $this->getPurchaseOrderPaymentHistoryData($purchase->id),
            ]);

        } catch (\Exception $e) {
                DB::rollback();

                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                ], 409);

        }
    }

    function getPurchaseOrderPaymentHistoryData($id){
        $model = PurchaseOrder::with('party','paymentTransaction.paymentType')->find($id);

        $data = [
            'party_id'  => $model->party->id,
            'party_name'  => $model->party->first_name.' '.$model->party->last_name,
            'balance'  => $this->formatWithPrecision($model->grand_total - $model->paid_amount),
            'invoice_id'  => $id,
            'invoice_code'  => $model->purchase_code,
            'invoice_date'  => $this->toUserDateFormat($model->purchase_date),
            'balance_amount'  => $this->formatWithPrecision($model->grand_total - $model->paid_amount),
            'paid_amount'  => $this->formatWithPrecision($model->paid_amount),
            'paid_amount_without_format'  => $model->paid_amount,
            'paymentTransactions' => $model->paymentTransaction->map(function ($transaction) {
                                        return [
                                            'payment_id' => $transaction->id,
                                            'transaction_date' => $this->toUserDateFormat($transaction->transaction_date),
                                            'reference_no' => $transaction->reference_no??'',
                                            'payment_type' => $transaction->paymentType->name, 
                                            'amount' => $this->formatWithPrecision($transaction->amount),
                                        ];
                                    })->toArray(),
        ];
        return $data;
    }

    public function purchasePaymentPage() : View
    {
        $suppliers = DB::table('parties')
            ->where('party_type', 'supplier')
            ->where('status', 1)->get();
            $users = DB::table('users')->where('status', 1)->get();
              $categories = DB::table('party_categories')->where('status', 1)->pluck('name', 'id');
        return view('purchase.payment.create', compact('suppliers', 'users', 'categories'));
    }

    public function getSupplierOrders(Request $request)
    {
         $supplierId = $request->supplier_id;

        // Fetch supplier orders
        $orders = Purchase::where('party_id', $supplierId)->get();

        // Total order amount
        $totalOrders = $orders->sum('grand_total');

        // Total paid (sum of payments.amount)
         $totalPaid = $totalOrders - $orders->sum(function ($order) {
            return $order->grand_total - $order->paid_amount;
        });

        // Remaining
        $totalPaid = SupplierPayment::where('party_id', $supplierId)->sum('amount');
         $remaining = $totalOrders - $totalPaid;

        return response()->json([
            'html' => view('purchase.payment.partials.supplier-orders', compact('orders','totalOrders','totalPaid','remaining'))->render(),
            'orders' => $orders,
        ]);
    }


   
    public function supplierOrdersPaymentStore(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:parties,id',
            'amount' => 'required|numeric',
            'payment_type_id' => 'required',
            'payment_note' => 'nullable|string|max:255',
            'payment_date' => 'required',
            'user' => 'nullable',
        ]);
        try {
        DB::beginTransaction();

        // Get all orders of customer
        $orders = Purchase::where('party_id', $validated['supplier_id'])->get();
        $totalAmount = $orders->sum('grand_total');

        // Already paid before this new payment
        $alreadyPaid = SupplierPayment::where('party_id', $validated['supplier_id'])->sum('amount');

        // Remaining before this payment
        $remainingBefore = $totalAmount - $alreadyPaid;

        // Check if already fully paid
        if ($remainingBefore <= 0) {
             DB::rollBack();
            return redirect()->back()->with('info', 'Already paid all dues. No remaining balance.');
        }

        // If entered amount is more than remaining, cap it
        $paymentAmount = min($validated['amount'], $remainingBefore);

        // New totals
        $newTotalPaid = $alreadyPaid + $paymentAmount;
        $remainingAmount = $totalAmount - $newTotalPaid;
         $validated['payment_date'] = Carbon::createFromFormat('d/m/Y', $validated['payment_date'])->format('Y-m-d');
        // Save payment
        $payment = SupplierPayment::create([
            'party_id' => $validated['supplier_id'],
            'amount' => $paymentAmount,
            'payment_type' => $validated['payment_type_id'],
            'total_amount' => $totalAmount,
            'paid_amount' => $newTotalPaid,
            'remaining_amount' => $remainingAmount,
            'payment_note' => $validated['payment_note'],
            'payment_date' => $validated['payment_date'],
            'created_by' => $validated['user'] ?? auth()->id(),
            'updated_by' => $validated['user'] ?? auth()->id(),
        ]);

          DB::commit();

        // Message if capped
        if ($validated['amount'] > $remainingBefore) {
            return redirect()->back()->with('error', 'Customer tried to pay more than remaining. Only ' . number_format($remainingBefore, 2) . ' was accepted.');
        }

        return redirect()->route('purchase.payments.create')->with('info', 'Payment recorded successfully.');
         } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }
}
