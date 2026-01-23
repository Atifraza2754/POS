<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Traits\FormatsDateInputs;

class PurchaseRequest extends FormRequest
{

    use FormatsDateInputs;

    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = true;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rulesArray = [
            'party_id'             => ['required', 'integer', Rule::exists('parties', 'id')->where('party_type','supplier')],
            'purchase_date'        => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
            'prefix_code'          => ['nullable', 'string','max:250'],
            'purchase_code'           => ['required', 'string','max:50'],
            'count_id'             => ['required', 'numeric'],
            'reference_no'           => ['nullable', 'string','max:50'],
            'round_off'            => ['nullable',Rule::requiredIf( fn () => empty($this->input('round_off'))),'numeric',],
            'grand_total'          => ['required', 'numeric'],
            'note'                 => ['nullable', 'string','max:250'],
            'state_id'             => ['nullable', 'integer', Rule::exists('states', 'id')],
            'row_count'            => ['required', 'integer', 'min:1'],
            'bilty'            => ['nullable'],
            'total_discount'            => ['nullable']
        ];

        //For Update Operation
        if ($this->isMethod('PUT')) {
            $rulesArray['purchase_id']          = ['required'];
        }

        //For Convert Opearation
        if ($this->input('operation') == 'convert') {
            //Which is Purchase Order Id
            $rulesArray['purchase_order_id']          = ['required'];
        }
        return $rulesArray;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        /**
         * @method formatDateInput
         * Defined in Trait FormatsDateInputs
         * */
        $purchaseDate  = $this->input('purchase_date');
        $this->merge([
            'purchase_date' => $this->toSystemDateFormat($purchaseDate),
            'purchase_code' => $this->getPurchaseCode(),
            'state_id' => (!empty($this->input('state_id'))) ? $this->input('state_id') : null,
        ]);
    }

    /**
     *
     * @return string
     */
    protected function getPurchaseCode()
    {
        $prefixCode = $this->input('prefix_code');
        $countId = $this->input('count_id');

        return $prefixCode . $countId;
    }

    public function messages(): array
    {
        $responseMessages = [
            'row_count.min'     => __('item.please_select_items'),
        ];

        if ($this->isMethod('PUT')) {
            $responseMessages['id.required']    = 'ID Not found to update record';
        }
        return $responseMessages;
    }

}
