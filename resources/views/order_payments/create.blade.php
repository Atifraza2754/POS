@extends('layouts.app')
@section('title', __('order.create'))

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'Order Payment',
                                            'Pay Order Payment',
                                        ]"/>
                <div class="row">
                    <form class="row g-3 needs-validation" method="POST" action="{{ route('order.payment.store') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf

                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" name="total_amount" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">
                        <div class="col-12 col-lg-12">
                            <div class="card">
                                <div class="card-header px-4 py-3">
                                    <h5 class="mb-0">{{ __('order.details') }}</h5>
                                </div>
                                <div class="card-body p-4 row g-3">
                                        @if (session('info'))
                                    <span class="alert alert-success" >{{session('info')}}</span>
                                @endif
                                  @if (session('error'))
                                    <span class="alert alert-danger" >{{session('error')}}</span>
                                @endif
                                        <div class="col-md-6">
                                                <x-label for="party_id" name="{{ __('customer.customer') }}" />
                                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Search by name, mobile, phone, whatsApp, email"><i class="fadeIn animated bx bx-info-circle"></i></a>
                                                <div class="input-group">
                                                   
                                                    <select class="form-select single-select-clear-field" id="party_id" name="party_id" data-placeholder="Choose Customer">
                                                        <option></option>
                                                        @foreach ($customers as $customer)
                                                            <option value="{{ $customer->id }}">{{ $customer->first_name }} {{$customer->last_name}}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="input-group-text open-party-model" data-party-type='customer'>
                                                        <i class='text-primary bx bx-plus-circle'></i>
                                                    </button>
                                                </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="">Customer Order</label>
                                            <div id="customer-orders">
                                                {{-- Orders will be loaded here via AJAX --}}
                                            </div>
                                        </div>
                                        
                                </div>
                               
                                
                                <div class="card-body p-4 row g-3">
                                        <div class="col-md-6">
                                            <x-label for="amount" name="{{ __('payment.amount') }}" />
                                            <div class="input-group mb-3">
                                                <x-input type="number" name="amount" value=""/>
                                                <span class="input-group-text" id="input-near-focus" role="button">RS</span>
                                            </div>
                                            @error('amount')
                                                <span class="text-danger mt-2" >{{$message}}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <x-label for="customer_id" name="{{ __('payment.type') }}" />
                                            <div class="input-group">
                                                <x-dropdown-payment-type selected="" />
                                                <button type="button" class="input-group-text" data-bs-toggle="modal" data-bs-target="#paymentTypeModal">
                                                    <i class='text-primary bx bx-plus-circle'></i>
                                                </button>
                                            </div>
                                             @error('payment_type_id')
                                                <span class="text-danger mt-2" >{{$message}}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="payment_date">Payment Date</label>
                                            <div class="input-group mb-3">
                                                <x-input type="text" additionalClasses="datepicker" name="payment_date" :required="true" value=""/>
                                                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                            </div>
                                           
                                        </div>
                                        {{-- <div class="col-md-6">
                                            <x-label for="payment_note" name="{{ __('payment.note') }}" />
                                            <x-textarea name="payment_note" value=""/>
                                        </div> --}}
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
                                        
                                        <div class="col-md-6">
                                            <x-label for="payment_note" name="{{ __('payment.note') }}" />
                                            <x-textarea name="payment_note" value=""/>
                                        </div>
                                </div>
                               

                                <div class="card-body p-4 row g-3">
                                        <div class="col-md-12">
                                            <div class="d-md-flex d-grid align-items-center gap-3">
                                                <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                                <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
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
        @include("modals.party.create")
        @include("modals.payment-type.create")

        @endsection

@section('js')
<script src="{{ asset('custom/js/order/order.js') }}"></script>
<script src="{{ asset('custom/js/modals/service/service.js') }}"></script>
<script src="{{ asset('custom/js/modals/party/party.js') }}"></script>
<script src="{{ asset('custom/js/modals/payment-type/payment-type.js') }}"></script>
<script src="{{ asset('custom/js/common/common.js') }}"></script>

<script>
$(document).ready(function () {
    $('#party_id').on('change', function () {
        const customerId = $(this).val();

        if (!customerId) return; // guard clause

        $.ajax({
            url: '{{ route("get.customer.orders") }}', // Laravel route
            method: 'GET',
            data: { customer_id: customerId },
            success: function (response) {
                $('#customer-orders').html(response.html); // assuming you're returning HTML
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                $('#customer-orders').html('<p class="text-danger">Failed to fetch orders.</p>');
            }
        });
    });
});
</script>

@endsection
