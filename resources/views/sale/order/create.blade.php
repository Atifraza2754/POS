@extends('layouts.app')
@section('title', __('sale.order.order'))

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'sale.sale',
                                            'sale.order.list',
                                            'sale.order.create',
                                        ]"/>
                <div class="row">
                    <form class="g-3 needs-validation" id="invoiceForm" action="{{ route('sale.order.store') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf
                        @method('POST')

                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" name="row_count_payments" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">
                        <input type="hidden" id="operation" name="operation" value="save">
                        <input type="hidden" id="selectedPaymentTypesArray" value="{{ $selectedPaymentTypesArray }}">

                        <!-- Temprary usage -->
                        <input type="hidden" name="batch_details_json" value=''>

                        <div class="row">
                            <div class="col-12 col-lg-12">
                                <div class="card">
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('sale.order.details') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-4">
                                                <x-label for="party_id" name="{{ __('customer.customer') }}" />

                                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Search by name, mobile, phone, whatsApp, email"><i class="fadeIn animated bx bx-info-circle"></i></a>

                                                <div class="input-group">
                                                    <select class="form-select party-ajax" data-party-type='customer' data-placeholder="Select Customer" id="party_id" name="party_id">
                                                        <x-option-default-party-selected partyType='customer' />
                                                    </select>
                                                    <button type="button" class="input-group-text open-party-model" data-party-type='customer'>
                                                        <i class='text-primary bx bx-plus-circle'></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="order_date" name="{{ __('app.date') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" additionalClasses="datepicker" name="order_date" :required="true" value=""/>
                                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="due_date" name="{{ __('app.due_date') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" additionalClasses="datepicker-edit" name="due_date" :required="true" value=""/>
                                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="order_code" name="{{ __('sale.order.code') }}" />
                                                <!--  -->
                                                <div class="input-group mb-3">
                                                    <x-input type="text" name="prefix_code" :required="true" placeholder="Prefix Code" value="{{ $data['prefix_code'] }}"/>
                                                    <span class="input-group-text">#</span>
                                                    <x-input type="text" name="count_id" :required="true" placeholder="Serial Number" value="{{ $data['count_id'] }}"/>
                                                </div>
                                            </div>
                                            @if(auth()->user()->role_id == 1)
                                            <div class="col-md-6">
                                                {{-- <label for="order_code">Select Salesman</label> --}}
                                                 <x-label for="state_id" name="{{ __('Select Salesman') }}" />
                                                <!--  -->
                                                <select class="form-select single-select-clear-field" id="user" name="user" data-placeholder="Choose one thing">
                                                    <option></option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @endif
                                            @if(app('company')['tax_type'] == 'gst')
                                            <div class="col-md-4">
                                                <x-label for="state_id" name="{{ __('app.state_of_supply') }}" />
                                                <x-dropdown-states selected="" dropdownName='state_id'/>
                                            </div>
                                            @endif

                                    </div>
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('item.items') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-3">
                                                <x-label for="warehouse_id" name="{{ __('warehouse.warehouse') }}" />
                                                <x-dropdown-warehouse selected="" dropdownName='warehouse_id' />
                                            </div>
                                            <div class="col-md-9">
                                                <x-label for="search_item" name="{{ __('item.enter_item_name') }}" />
                                                <div class="input-group">
                                                    <span class="input-group-text" id="basic-addon1"><i class="fadeIn animated bx bx-barcode-reader text-primary"></i></span>
                                                    <input type="text" id="search_item" value="" class="form-control" required placeholder="Scan Barcode/Search Items">
                                                    
                                                </div>
                                            </div>
                                            <div class="col-md-12 table-responsive">
                                                <table class="table mb-0 table-striped table-bordered" id="invoiceItemsTable">
                                                    <thead>
                                                        <tr class="text-uppercase">
                                                            <th scope="col">{{ __('app.action') }}</th>
                                                            <th scope="col">{{ __('item.item') }}</th>
                                                            {{-- <th scope="col" class="{{ !app('company')['enable_serial_tracking'] ? 'd-none':'' }}">{{ __('item.serial') }}</th> --}}
                                                            {{-- <th scope="col" class="{{ !app('company')['enable_batch_tracking'] ? 'd-none':'' }}">{{ __('item.batch_no') }}</th> --}}
                                                            {{-- <th scope="col" class="{{ !app('company')['enable_mfg_date'] ? 'd-none':'' }}">{{ __('item.mfg_date') }}</th> --}}
                                                            {{-- <th scope="col" class="{{ !app('company')['enable_exp_date'] ? 'd-none':'' }}">{{ __('item.exp_date') }}</th> --}}
                                                            <th scope="col" class="{{ !app('company')['enable_model'] ? 'd-none':'' }}">{{ __('item.model_no') }}</th>
                                                            {{-- <th scope="col" class="{{ !app('company')['show_mrp'] ? 'd-none':'' }}">{{ __('item.mrp') }}</th> --}}
                                                            <th scope="col" class="{{ !app('company')['enable_color'] ? 'd-none':'' }}">{{ __('item.color') }}</th>
                                                            <th scope="col" class="{{ !app('company')['enable_size'] ? 'd-none':'' }}">{{ __('item.size') }}</th>
                                                            <th scope="col">{{ __('app.qty') }}</th>
                                                            <th scope="col">{{ __('unit.unit') }}</th>
                                                            <th scope="col">{{ __('app.price_per_unit') }}</th>
                                                            {{-- <th scope="col" class="{{ !app('company')['show_discount'] ? 'd-none':'' }}">{{ __('app.discount') }}</th> --}}
                                                            <th scope="col" class="{{ (app('company')['tax_type'] == 'no-tax') ? 'd-none':'' }}">{{ __('tax.tax') }}</th>
                                                            <th scope="col">{{ __('app.total') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="8" class="text-center fw-light fst-italic default-row">
                                                                No items are added yet!!
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="2" class="fw-bold text-end tfoot-first-td">
                                                                {{ __('app.total') }}
                                                            </td>
                                                            <td class="fw-bold sum_of_quantity">
                                                                0
                                                            </td>
                                                            <td class="fw-bold text-end" colspan="4"></td>
                                                            <td class="fw-bold text-end sum_of_total">
                                                                0
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            {{-- <div class="col-md-8">
                                                <x-label for="note" name="{{ __('app.note') }}" />
                                                <x-textarea name='note' value=''/>
                                            </div> --}}
                                            <div class="col-md-4 mt-4">
                                                <table class="table mb-0 table-striped">
                                                   <tbody>
                                                      <tr class="d-none">
                                                         <td class="w-50">
                                                            <div class="form-check">
                                                                <label class="form-check-label fw-bold cursor-pointer" for="round_off_checkbox">{{ __('Discount') }}</label>
                                                            </div>
                                                        </td>
                                                         <td class="w-50">
                                                            <x-input type="integer" additionalClasses="text-end cu_numeric round_off " name="round_off" :required="false" placeholder="Round-Off" value="0"/>
                                                        </td>
                                                      </tr>
                                                      {{-- <tr class="">
                                                         <td class="w-50">
                                                            <div class="form-check">
                                                                <label class="form-check-label fw-bold cursor-pointer" for="discount">{{ __('Discount') }}</label>
                                                            </div>
                                                        </td>
                                                         <td class="w-50">
                                                            <x-input type="integer" additionalClasses="text-end " name="discount" :required="false" placeholder="Discount" value="0"/>
                                                        </td>
                                                      </tr>
                                                      <tr>
                                                         <td><span class="fw-bold">{{ __('app.grand_total') }}</span></td>
                                                         <td>
                                                            <x-input type="text" additionalClasses="text-end grand_total" readonly=true name="grand_total" :required="true" placeholder="Round-Off" value="0"/>
                                                        </td>
                                                      </tr> --}}
                                                    <tr>
                                                        <td class="w-50">
                                                            <div class="form-check">
                                                                <label class="form-check-label fw-bold cursor-pointer" for="discount">{{ __('Discount (%)') }}</label>
                                                            </div>
                                                        </td>
                                                        <td class="w-50 position-relative">
                                                            <x-input 
                                                                type="number" 
                                                                additionalClasses="text-end pe-4" 
                                                                name="discount" 
                                                                :required="true" 
                                                                placeholder="0%" 
                                                                value="0"
                                                                min="0" 
                                                                max="100"
                                                            />
                                                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted">%</span>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td><span class="fw-bold">{{ __('app.grand_total') }}</span></td>
                                                        <td>
                                                            <x-input type="text" additionalClasses="text-end grand_total" readonly=true name="grand_total" :required="true" placeholder="Round-Off" value="0"/>
                                                        </td>
                                                    </tr> 

                                                    <tr>
                                                        <td><span class="fw-bold">{{ __('Discounted Total') }}</span></td>
                                                        <td>
                                                            <x-input 
                                                                type="text" 
                                                                additionalClasses="text-end text-success grand_total" 
                                                                readonly=true 
                                                                name="discounted_total" 
                                                                placeholder="0.00" 
                                                                value="0"
                                                            />
                                                        </td>
                                                    </tr>


                                                   </tbody>
                                                </table>
                                            </div>
                                    </div>
                                    {{-- <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('payment.payment') }}</h5>
                                    </div> --}}
                                    <div class="card-body p-4 row g-3 ">
                                        <div class="payment-container">
                                            <div class="row payment-type-row-0 py-3 ">
                                                <div class="col-md-6">
                                                    <x-label for="amount" id="amount_lang" labelDataName="{{ __('payment.amount') }}" name="<strong>#1</strong> {{ __('payment.amount') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" additionalClasses="cu_numeric" name="payment_amount[0]" value=""/>
                                                        <span class="input-group-text" id="input-near-focus" role="button">RS</span>
                                                    </div>
                                                </div>
                                                {{-- <div class="col-md-6">
                                                    <x-label for="payment_type" id="payment_type_lang" name="{{ __('payment.type') }}" />
                                                    <div class="input-group">
                                                        <select class="form-select select2 payment-type-ajax" name="payment_type_id[0]" data-placeholder="Choose one thing">
                                                        </select>

                                                        <button type="button" class="input-group-text" data-bs-toggle="modal" data-bs-target="#paymentTypeModal">
                                                            <i class='text-primary bx bx-plus-circle'></i>
                                                        </button>
                                                    </div>
                                                </div> --}}
                                                {{-- <div class="col-md-6">
                                                    <x-label for="payment_note" id="payment_note_lang" name="{{ __('payment.note') }}" />
                                                    <x-textarea name="payment_note[0]" value=""/>
                                                </div> --}}
                                            </div>
                                        </div>
                                        {{-- <div class="col-md-4">
                                            <x-anchor-tag class="add_payment_type" href="javascript:;" text="<div class='d-flex align-items-center'><i class='fadeIn animated bx bx-plus font-30 text-primary'></i><div class=''>{{ __('payment.add_payment_type') }}</div></div>" />
                                        </div> --}}
                                    </div>

                                    <div class="card-header px-4 py-3"></div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-12">
                                                <div class="d-md-flex d-grid align-items-center gap-3">
                                                    <x-button type="button" class="primary px-4" buttonId="submit_form" text="{{ __('app.submit') }}" />
                                                    <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
                <!--end row-->
            </div>
        </div>
        <!-- Import Modals -->
        @include("modals.service.create")
        @include("modals.expense-category.create")
        @include("modals.payment-type.create")
        @include("modals.item.serial-tracking")
        @include("modals.item.batch-tracking-sale")
        @include("modals.party.create")
        @include("modals.item.create")

        @endsection

@section('js')
<script src="{{ asset('custom/js/sale/sale-order.js') }}"></script>
<script src="{{ asset('custom/js/items/serial-tracking.js') }}"></script>
<script src="{{ asset('custom/js/items/serial-tracking-settings.js') }}"></script>
<script src="{{ asset('custom/js/items/batch-tracking-sale.js') }}"></script>
<script src="{{ asset('custom/js/modals/payment-type/payment-type.js') }}"></script>
<script src="{{ asset('custom/js/payment-types/payment-type-select2-ajax.js') }}"></script>
<script src="{{ asset('custom/js/common/common.js') }}"></script>
<script src="{{ asset('custom/js/modals/party/party.js') }}"></script>
<script src="{{ asset('custom/js/modals/item/item.js') }}"></script>
@endsection
