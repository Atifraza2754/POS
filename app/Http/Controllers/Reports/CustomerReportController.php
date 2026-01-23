<?php

namespace App\Http\Controllers\Reports;

use App\Traits\FormatNumber; 
use App\Traits\FormatsDateInputs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Party\Party;
use App\Models\CustomerPayment;
use App\Models\Sale\SaleOrder;
use App\Services\PartyService;

class CustomerReportController extends Controller
{
    use FormatsDateInputs;

    use FormatNumber;

    public $partyService;

    function __construct(PartyService $partyService)
    {
        $this->partyService = $partyService;
    }
    // public function getDuePaymentsRecords(Request $request) : JsonResponse{
    //     try{
    //         $partyId             = $request->input('party_id');

    //         $preparedData = CustomerPayment::when($partyId, function ($query) use ($partyId) {
    //                                     return $query->where('party_id', $partyId);
    //                                 })
    //                                 // ->where('party_type', 'customer')
    //                                 ->get();
            
    //         if($preparedData->count() == 0){
    //             throw new \Exception('No Records Found!!');
    //         }

    //         $recordsArray = [];

    //         foreach ($preparedData as $data) {

    //             $balanceData = $this->partyService->getPartyBalance($data->id);

    //             if($balanceData['balance'] != 0){

    //                 $status = '';
    //                 $className = '';
    //                 $balance = $balanceData['balance'];

    //                 if($balanceData['status']=='you_collect'){
    //                     $status = 'You Collect';
    //                 }elseif($balanceData['status']=='you_pay'){
    //                     $status = 'You Pay';
    //                     $balance = -$balanceData['balance'];
    //                     $className = 'text-danger';
    //                 }else{
    //                     $status = 'No Balance';
    //                 }

    //                 $recordsArray[] = [  
    //                                 'party_name'            => $data->getFullName(),
    //                                 'due_amount'            => $this->formatWithPrecision($balance, comma:false),
    //                                 'status'                => $status,
    //                                 'className'             => $className,
    //                             ];
    //             }
                
    //         }
            
    //         return response()->json([
    //                     'status'    => true,
    //                     'message' => "Records are retrieved!!",
    //                     'data' => $recordsArray,
    //                 ]);
    //     } catch (\Exception $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $e->getMessage(),
    //             ], 409);

    //     }
    // }



    // public function getDuePaymentsRecords(Request $request): JsonResponse
    // {
    //     try {
    //         $partyId = $request->input('party_id');

    //         // ✅ Build query: Sum the remaining_amount per customer
    //         $query = CustomerPayment::select('party_id')
    //             ->selectRaw('SUM(remaining_amount) as remaining_amount')
    //             ->with('party')
    //             ->groupBy('party_id');

    //         if (!empty($partyId)) {
    //             $query->where('party_id', $partyId);
    //         }

    //         $preparedData = $query->get();

    //         if ($preparedData->count() == 0) {
    //             throw new \Exception('No Records Found!!');
    //         }

    //         $recordsArray = [];

    //         foreach ($preparedData as $data) {

    //             $recordsArray[] = [
    //                 'party_id'   => $data->party_id,
    //                 'party_name' => $data->party?->getFullName() ?? 'Unknown',
    //                 'due_amount' => $this->formatWithPrecision($data->remaining_amount, comma: false),
    //                 'status' => 'Remaining',
    //                 "className"=> "text-danger"
                
    //             ];
    //         }

    //         return response()->json([
    //             'status'  => true,
    //             'message' => "Due payment records retrieved successfully!",
    //             'data'    => $recordsArray,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //         ], 409);
    //     }
    // }



    public function getDuePaymentsRecords(Request $request): JsonResponse
    {
        try {
            $partyId = $request->input('party_id');

            /**
             * 1️⃣ Get total sales per customer from sale_orders
             *    (Use grand_total column)
             */
            $salesQuery = SaleOrder::select('party_id')
                ->selectRaw('SUM(grand_total) as total_sales')
                ->groupBy('party_id');

            if (!empty($partyId)) {
                $salesQuery->where('party_id', $partyId);
            }

            $salesData = $salesQuery->pluck('total_sales', 'party_id');

            /**
             * 2️⃣ Get total payments per customer from customer_payments
             *    (Use amount column)
             */
            $paymentsQuery = CustomerPayment::select('party_id')
                ->selectRaw('SUM(amount) as total_paid')
                ->groupBy('party_id');

            if (!empty($partyId)) {
                $paymentsQuery->where('party_id', $partyId);
            }

            $paymentsData = $paymentsQuery->pluck('total_paid', 'party_id');

            /**
             * 3️⃣ Combine both datasets and calculate remaining dues
             */
            $allPartyIds = $salesData->keys()->merge($paymentsData->keys())->unique();

            if ($allPartyIds->isEmpty()) {
                throw new \Exception('No Records Found!!');
            }

            $recordsArray = [];

            foreach ($allPartyIds as $id) {
                $totalSales = (float) ($salesData[$id] ?? 0);
                $totalPaid  = (float) ($paymentsData[$id] ?? 0);
                $dueAmount  = $totalSales - $totalPaid;

                // Skip customers who have no sales or zero balance
                if ($totalSales == 0 && $dueAmount == 0) {
                    continue;
                }

                $party = Party::find($id); // adjust this if you have a different relationship

                $recordsArray[] = [
                    'party_id'     => $id,
                    'party_name'   => $party?->getFullName() ?? 'Unknown',
                    'total_sales'  => $this->formatWithPrecision($totalSales, comma: false),
                    'total_paid'   => $this->formatWithPrecision($totalPaid, comma: false),
                    'due_amount'   => $this->formatWithPrecision($dueAmount, comma: false),
                    'status'       => 'Remaining',
                    'className'    => $dueAmount > 0 ? 'text-danger' : '',
                ];
            }

            if (empty($recordsArray)) {
                throw new \Exception('No Records Found!!');
            }

            return response()->json([
                'status'  => true,
                'message' => "Due payment records retrieved successfully!",
                'data'    => $recordsArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }



    
}
