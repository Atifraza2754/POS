@extends('layouts.app')
@section('title', 'Customer History')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection
		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
					<x-breadcrumb :langArray="[
                                            'Customer ',
                                            'Customer History',
                                        ]"/>

                    <div class="card">

					<div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
					    <!-- Other content on the left side -->
					    <div>
					    	<h5 class="mb-0 text-uppercase">Customer History</h5>
					    </div>
					   
					</div>
					<div class="card-body">
						@if(auth()->user()->role_id == 1)
							<label>
								<input type="checkbox" name="reached_credit_limit" value="1" {{ request('reached_credit_limit') ? 'checked' : '' }}>
								Show customers who reached credit limit
							</label>
						@endif
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
										<th>Customer</th>
										<th>Mobile</th>
										<th>Total Amount</th>
										<th>Paid Amount</th>
										<th>Remaining Amount</th>
										<th>Credit Limit</th>
										<th>Created By</th>
										<th>Total Orders</th>
										<th>Created At</th>
										{{-- <th>Action</th> --}}

									</tr>
								</thead>
							</table>
                        </form>
						</div>
					</div>
					</div>
				</div>
				<!--end row-->
			</div>


@endsection
@section('js')
<script src="{{ asset('custom/js/common/common.js') }}"></script>
<script src="{{ asset('custom/js/party/party-history.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('custom/js/common/common.js') }}"></script>
{{-- <script src="{{ asset('custom/js/party/party-list.js') }}"></script> --}}
@endsection
