@extends('layouts.app')
@section('title', __('order.list'))

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection
		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
					<x-breadcrumb :langArray="[
											'Order Payments',
                                            'Payment History',
										]"/>

                    <div class="card">
						@if (session('info'))
							<span class="alert alert-success" >{{session('info')}}</span>
						@endif
						@if (session('error'))
							<span class="alert alert-danger" >{{session('error')}}</span>
						@endif
					<div class="card-header px-4 py-3">
					    <!-- Other content on the left side -->
						<div class="row">
							<div class="col-md-12">
								<div>
									<h5 class="mb-0 text-uppercase">{{ __('Cash In') }}</h5>
								</div>
							</div>
						</div>
					    
					    
					    {{-- @can('customer.create')
					    <!-- Button pushed to the right side -->
					    <x-anchor-tag href="{{ route('order.create') }}" text="{{ __('order.create') }}" class="btn btn-primary px-5" />
					    @endcan --}}
						@if(auth()->user()->role_id ==1)
						<div class="row g-3 mt-3">
                            {{-- @if(auth()->user()->role_id == 1)
							<label>
								<input type="checkbox" name="reached_credit_limit" value="1" {{ request('reached_credit_limit') ? 'checked' : '' }}>
								Show customers who reached credit limit
							</label>
						@endif --}}

                            <div class="col-md-4">
                                <x-label for="user_id" name="{{ __('user.user') }}" />
                                <x-dropdown-user selected="" :showOnlyUsername='true' />
                            </div>
                            <div class="col-md-4">
                                <x-label for="from_date" name="{{ __('app.from_date') }}" />
                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Order Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
                                <div class="input-group mb-3">
                                    <x-input type="text" additionalClasses="datepicker-edit" name="from_date" :required="true" value=""/>
                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <x-label for="to_date" name="{{ __('app.to_date') }}" />
                                <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Order Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
                                <div class="input-group mb-3">
                                    <x-input type="text" additionalClasses="datepicker-edit" name="to_date" :required="true" value=""/>
                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                </div>
                            </div>
                        </div>
						@endif
					</div>
					<div class="card-body">
						<div class="table-responsive">
                        <form class="row g-3 needs-validation" id="datatableForm" action="{{ route('order.payment.delete') }}" enctype="multipart/form-data">
                            {{-- CSRF Protection --}}
                            @csrf
                            @method('POST')
							<table class="table table-striped table-bordered border w-100" id="datatable">
								<thead>
									<tr>
										<th class="d-none"></th> <!-- hidden ID -->
										<th><input type="checkbox"></th>
										{{-- <th>Customer</th> --}}
										<th>Salesman</th>	
										<th>Total Amount</th>
										{{-- <th>Paid Amount</th>
										<th>Remaining Amount</th> --}}
										<th>Payment Date</th>
										<th>Action</th>
									</tr>
								</thead>
							</table>
                        </form>
						</div>
					</div>
				</div>
					</div>
				</div>
				<!--end row-->
			</div>
		</div>
		@endsection
@section('js')
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('custom/js/common/common.js') }}"></script>
{{-- <script src="{{ asset('custom/js/order/order-list.js') }}"></script> --}}
<script src="{{ asset('custom/js/order/cash-in-payment-list.js') }}"></script>
@endsection
