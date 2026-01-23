@extends('layouts.app')
@section('title', __('app.dashboard'))

		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
                    @if (auth()->user()->role_id == 2) 
	

<div class="container mt-4">
    <div class="row g-3">

        <!-- Customer List -->
        <div class="col-6">
            <a href="{{ route('party.list', ['partyType' => 'customer']) }}" class="text-decoration-none">
                <div class="card shadow-sm text-center p-3 h-100">
                    <div class="mb-2">
                        <!-- Customer Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4CAF50" viewBox="0 0 24 24">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2V19.2c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>
                    <h6 class="fw-bold text-dark">Customer List</h6>
                </div>
            </a>
        </div>

        <!-- Customer History -->
        <div class="col-6">
            <a href="{{ route('party.page') }}" class="text-decoration-none">
                <div class="card shadow-sm text-center p-3 h-100">
                    <div class="mb-2">
                        <!-- History Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4CAF50" viewBox="0 0 24 24">
                            <path d="M13 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-7-7V3z"/><path d="M12 8h1v5l4 2-.5.9L12 14V8z"/>
                        </svg>
                    </div>
                    <h6 class="fw-bold text-dark">Customer History</h6>
                </div>
            </a>
        </div>

        <!-- Customer Category -->
        {{--
        <div class="col-6">
            <a href="{{ route('party.category.list') }}" class="text-decoration-none">
                <div class="card shadow-sm text-center p-3 h-100">
                    <div class="mb-2">
                        <!-- Category Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4CAF50" viewBox="0 0 24 24">
                            <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                        </svg>
                    </div>
                    <h6 class="fw-bold text-dark">Customer Category</h6>
                </div>
            </a>
        </div>
        --}}

        <!-- Order List -->
        <div class="col-6">
            <a href="{{ route('sale.order.list') }}" class="text-decoration-none">
                <div class="card shadow-sm text-center p-3 h-100">
                    <div class="mb-2">
                        <!-- List Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4CAF50" viewBox="0 0 24 24">
                            <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/>
                        </svg>
                    </div>
                    <h6 class="fw-bold text-dark">Order List</h6>
                </div>
            </a>
        </div>

        <!-- Create Order -->
        <div class="col-6">
            <a href="{{ route('sale.order.create') }}" class="text-decoration-none">
                <div class="card shadow-sm text-center p-3 h-100">
                    <div class="mb-2">
                        <!-- Plus Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#4CAF50"><path d="M440-600v-120H320v-80h120v-120h80v120h120v80H520v120h-80ZM280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM40-800v-80h131l170 360h280l156-280h91L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68.5-39t-1.5-79l54-98-144-304H40Z"/></svg>
                    </div>
                    <h6 class="fw-bold text-dark">Create Order</h6>
                </div>
            </a>
        </div>

        <!-- Create Order Payment -->
        <div class="col-6">
            <a href="{{ route('order-payments.create') }}" class="text-decoration-none">
                <div class="card shadow-sm text-center p-3 h-100">
                    <div class="mb-2">
                        <!-- Wallet Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4CAF50" viewBox="0 0 24 24">
                            <path d="M21 7H3V5H21V7M21 11H3V9H21V11M3 13H21V19H3V13Z"/>
                        </svg>
                    </div>
                    <h6 class="fw-bold text-dark">Create Order Payment</h6>
                </div>
            </a>
        </div>

        <!-- Order Payment History -->
        <div class="col-6">
            <a href="{{ route('order.payment.history.list') }}" class="text-decoration-none">
                <div class="card shadow-sm text-center p-3 h-100">
                    <div class="mb-2">
                        <!-- Document Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4CAF50" viewBox="0 0 24 24">
                            <path d="M6 2C4.89 2 4 2.89 4 4V20A2 2 0 0 0 6 22H18C19.11 22 20 21.11 20 20V8L14 2H6Z"/>
                        </svg>
                    </div>
                    <h6 class="fw-bold text-dark">Order Payment History</h6>
                </div>
            </a>
        </div>

    </div>
</div>



						   
					 @endif
                @can('dashboard.can.view.widget.cards')
				{{-- <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
                   <div class="col">
					 <div class="card radius-10 border-start border-0 border-4 border-info">
						<div class="card-body">
							<div class="d-flex align-items-center">
								<div>
									<p class="mb-0 text-secondary">{{ __('sale.order.pending') }}</p>
									<h4 class="my-1 text-info">{{ $pendingSaleOrders }}</h4>

								</div>
								<div class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i class='bx bxs-cart'></i>
								</div>
							</div>
						</div>
					 </div>
				   </div>
				   <div class="col">
					<div class="card radius-10 border-start border-0 border-4 border-success">
					   <div class="card-body">
						   <div class="d-flex align-items-center">
							   <div>
								   <p class="mb-0 text-secondary">{{ __('sale.order.completed') }}</p>
									<h4 class="my-1 text-success">{{ $totalCompletedSaleOrders }}</h4>

							   </div>
							   <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class='bx bxs-check-circle' ></i>
							   </div>
						   </div>
					   </div>
					</div>
				  </div>
				   <div class="col">
					<div class="card radius-10 border-start border-0 border-4 border-danger">
					   <div class="card-body">
						   <div class="d-flex align-items-center">
							   <div>
								   <p class="mb-0 text-secondary">{{ __('Total Supplier') }}</p>
									<h4 class="my-1 text-danger">{{ 1 }}</h4>

							   </div>
							   <div class="widgets-icons-2 rounded-circle bg-gradient-burning text-white ms-auto"><i class='bx bxs-down-arrow-circle'></i>
							   </div>
						   </div>
					   </div>
					</div>
				  </div>

				  <div class="col">
					<div class="card radius-10 border-start border-0 border-4 border-warning">
					   <div class="card-body">
						   <div class="d-flex align-items-center">
							   <div>
								   <p class="mb-0 text-secondary">{{ __('Orders') }}</p>
									<h4 class="my-1 text-warning">{{ 1 }}</h4>

							   </div>
							   <div class="widgets-icons-2 rounded-circle bg-gradient-orange text-white ms-auto"><i class='bx bxs-up-arrow-circle'></i>
							   </div>
						   </div>
					   </div>
					</div>
				  </div>
				</div><!--end row--> --}}

				{{-- <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
                   <div class="col">
					 <div class="card radius-10 border-start border-0 border-4 border-info">
						<div class="card-body">
							<div class="d-flex align-items-center">
								<div>
									<p class="mb-0 text-secondary">{{ __('purchase.order.pending') }}</p>
									<h4 class="my-1 text-info">{{ $pendingPurchaseOrders }}</h4>

								</div>
								<div class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i class='bx bxs-purchase-tag'></i>
								</div>
							</div>
						</div>
					 </div>
				   </div>
				   <div class="col">
					<div class="card radius-10 border-start border-0 border-4 border-success">
					   <div class="card-body">
						   <div class="d-flex align-items-center">
							   <div>
								   <p class="mb-0 text-secondary">{{ __('purchase.order.completed') }}</p>
									<h4 class="my-1 text-success">{{ $totalCompletedPurchaseOrders }}</h4>

							   </div>
							   <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class='bx bx-check-double' ></i>
							   </div>
						   </div>
					   </div>
					</div>
				  </div>
				   <div class="col">
					<div class="card radius-10 border-start border-0 border-4 border-danger">
					   <div class="card-body">
						   <div class="d-flex align-items-center">
							   <div>
								   <p class="mb-0 text-secondary">{{ __('expense.total_expenses') }}</p>
									<h4 class="my-1 text-danger">{{ $totalExpense }}</h4>
							   </div>
							   <div class="widgets-icons-2 rounded-circle bg-gradient-burning text-white ms-auto"><i class='bx bxs-minus-circle'></i>
							   </div>
						   </div>
					   </div>
					</div>
				  </div>

				  <div class="col">
					<div class="card radius-10 border-start border-0 border-4 border-warning">
					   <div class="card-body">
						   <div class="d-flex align-items-center">
							   <div>
								   <p class="mb-0 text-secondary">{{ __('customer.total') }}</p>
									<h4 class="my-1 text-warning">{{ $totalCustomers }}</h4>

							   </div>
							   <div class="widgets-icons-2 rounded-circle bg-gradient-orange text-white ms-auto"><i class='bx bxs-group'></i>
							   </div>
						   </div>
					   </div>
					</div>
				  </div>
				</div><!--end row--> --}}
				<div class="container mt-4 mb-4">
					<div class="row g-3">

						<!-- Customer List -->
						<div class="col-6">
							<a href="{{ route('party.create', ['partyType' => 'customer']) }}" class="text-decoration-none">
								<div class="card shadow-sm text-center p-3 h-100">
									<div class="mb-2">
										<!-- Customer Icon -->
										<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#008cff" viewBox="0 0 24 24">
											<path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2V19.2c0-3.2-6.4-4.8-9.6-4.8z"/>
										</svg>
									</div>
									<h6 class="fw-bold text-dark">Create Customer</h6>
								</div>
							</a>
						</div>

						<!-- Customer History -->
						<div class="col-6">
							<a href="{{ route('party.list', ['partyType' => 'customer']) }}" class="text-decoration-none">
								<div class="card shadow-sm text-center p-3 h-100">
									<div class="mb-2">
										<!-- History Icon -->
										<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#008cff" viewBox="0 0 24 24">
											<path d="M13 3a9 9 0 1 0 9 9h-2a7 7 0 1 1-7-7V3z"/><path d="M12 8h1v5l4 2-.5.9L12 14V8z"/>
										</svg>
									</div>
									<h6 class="fw-bold text-dark">Customer List</h6>
								</div>
							</a>
						</div>

						<!-- Customer Category -->
						{{--
						<div class="col-6">
							<a href="{{ route('party.category.list') }}" class="text-decoration-none">
								<div class="card shadow-sm text-center p-3 h-100">
									<div class="mb-2">
										<!-- Category Icon -->
										<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4CAF50" viewBox="0 0 24 24">
											<path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
										</svg>
									</div>
									<h6 class="fw-bold text-dark">Customer Category</h6>
								</div>
							</a>
						</div>
						--}}

						<!-- Order List -->
						<div class="col-6">
							<a href="{{ route('sale.order.list') }}" class="text-decoration-none">
								<div class="card shadow-sm text-center p-3 h-100">
									<div class="mb-2">
										<!-- List Icon -->
										<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#008cff" viewBox="0 0 24 24">
											<path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/>
										</svg>
									</div>
									<h6 class="fw-bold text-dark">Order List</h6>
								</div>
							</a>
						</div>

						<!-- Create Order -->
						<div class="col-6">
							<a href="{{ route('sale.order.create') }}" class="text-decoration-none">
								<div class="card shadow-sm text-center p-3 h-100">
									<div class="mb-2">
										<!-- Plus Icon -->
										<svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#008cff"><path d="M440-600v-120H320v-80h120v-120h80v120h120v80H520v120h-80ZM280-80q-33 0-56.5-23.5T200-160q0-33 23.5-56.5T280-240q33 0 56.5 23.5T360-160q0 33-23.5 56.5T280-80Zm400 0q-33 0-56.5-23.5T600-160q0-33 23.5-56.5T680-240q33 0 56.5 23.5T760-160q0 33-23.5 56.5T680-80ZM40-800v-80h131l170 360h280l156-280h91L692-482q-11 20-29.5 31T622-440H324l-44 80h480v80H280q-45 0-68.5-39t-1.5-79l54-98-144-304H40Z"/></svg>
									</div>
									<h6 class="fw-bold text-dark">Create Order</h6>
								</div>
							</a>
						</div>

						<!-- Create Order Payment -->
						<div class="col-6">
							<a href="{{ route('order-payments.create') }}" class="text-decoration-none">
								<div class="card shadow-sm text-center p-3 h-100">
									<div class="mb-2">
										<!-- Wallet Icon -->
										<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#008cff" viewBox="0 0 24 24">
											<path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 
											7.04a1.003 1.003 0 0 0 0-1.41l-2.34-2.34a1.003 
											1.003 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
										</svg>

									</div>
									<h6 class="fw-bold text-dark">Create Order Payment</h6>
								</div>
							</a>
						</div>

						<!-- Order Payment History -->
						<div class="col-6">
							<a href="{{ route('order.payment.history.list') }}" class="text-decoration-none">
								<div class="card shadow-sm text-center p-3 h-100">
									<div class="mb-2">
										<!-- Document Icon -->
										<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#008cff" viewBox="0 0 24 24">
											<path d="M6 2C4.89 2 4 2.89 4 4V20A2 2 0 0 0 6 22H18C19.11 22 20 21.11 20 20V8L14 2H6Z"/>
										</svg>
									</div>
									<h6 class="fw-bold text-dark">Order Payment History</h6>
								</div>
							</a>
						</div>

					</div>
				</div>
                @endcan
				<div class="row">
                    @can('dashboard.can.view.sale.vs.purchase.bar.chart')
                   <div class="col-12 col-lg-8 d-flex d-none">
                      <div class="card radius-10 w-100">
						<div class="card-header">
							<div class="d-flex align-items-center">
								<div>
									<h6 class="mb-0">{{ __('sale.sale_vs_purchase') }}</h6>
								</div>
							</div>
						</div>
						  <div class="card-body">
							<div class="d-flex align-items-center ms-auto font-13 gap-2 mb-3">
								<span class="border px-1 rounded cursor-pointer"><i class="bx bxs-circle me-1" style="color: #ffc107"></i>{{ __('purchase.purchase_bills') }}</span>
								<span class="border px-1 rounded cursor-pointer"><i class="bx bxs-circle me-1" style="color: #14abef"></i>{{ __('sale.sale_invoices') }}</span>
							</div>
							<div class="chart-container-1">
								<canvas id="chart1"></canvas>
							</div>
						  </div>
					  </div>
				   </div>
                   @endcan
                   @can('dashboard.can.view.trending.items.pie.chart')
				   <div class="col-12 col-lg-12 d-flex">
                       <div class="card radius-10 w-100">
						<div class="card-header">
							<div class="d-flex align-items-center">
								<div>
									<h6 class="mb-0">{{ __('item.trending') }}</h6>
								</div>
							</div>
						</div>
						   <div class="card-body">
							<div class="chart-container-2">
								<canvas id="chart2"></canvas>
							  </div>
						   </div>
						   <ul class="list-group list-group-flush">
								@foreach($trendingItems as $item)
								  <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center border-top">
								    {{ $item['name'] }}
								    <span class="badge bg-success rounded-pill">{{ $formatNumber->formatQuantity($item['total_quantity']) }}</span>
								  </li>
								@endforeach
						</ul>
					   </div>
				   </div>
                   @endcan
				</div><!--end row-->

                @can('dashboard.can.view.recent.invoices.table')
				 <div class="card radius-10">
					<div class="card-header">
						<div class="d-flex align-items-center">
							<div>
								<h6 class="mb-0">{{ __('Low Stock Table') }}</h6>
							</div>
						</div>
					</div>
                         <div class="card-body">
						 <div class="table-responsive">
						   <table id="lowStockTable" class="table align-middle mb-2 p-2">
							<thead class="table-light">
							 <tr>
								 <th>{{ __('Name') }}</th>
								 <th>{{ __('Remaing Quantity') }}</th>
								 <th>{{ __('Code') }}</th>
								 <th>{{ __('Created At') }}</th>
							   {{-- <th>{{ __('app.balance') }}</th> --}}
                        		{{-- <th>{{ __('app.status') }}</th> --}}
							 </tr>
							 </thead>
							 <tbody>
							 	@foreach($lowStock as $ls)

								 		<tr>
											 <td>{{ $ls->name }}</td>
								 			<td>{{ $ls->min_stock }}</td>
								 			<td>{{ $ls->item_code }}</td>
								 			<td>{{ $ls->created_at }}</td>
								 			
								 		</tr>

							 	@endforeach
						    </tbody>
						  </table>
						  <div>
							<div class="d-flex justify-content-end mt-3">
								{{ $lowStock->links() }}
						  </div>
						  </div>
						 </div>
					</div>
				 </div>

				<div class="card radius-10">
					<div class="card-header">
						<div class="d-flex align-items-center">
							<div>
								<h6 class="mb-0">{{ __('Recent Sales (Last 24 Hours)') }}</h6>
							</div>
						</div>
					</div>

					<div class="card-body">
						<div class="table-responsive">
							<form class="row g-3 needs-validation" id="datatableForm" action="{{ route('sale.order.delete') }}" enctype="multipart/form-data">
                            {{-- CSRF Protection --}}
                            @csrf
                            @method('POST')
                            <input type="hidden" id="base_url" value="{{ url('/') }}">
                            <div class="">
                                <table class="table table-striped table-bordered border w-100" id="datatable">
                                    <thead>
                                        <tr>
                                            <th class="d-none"><!-- Which Stores ID & it is used for sorting --></th>
                                            <th><input class="form-check-input row-select" type="checkbox"></th>
                                            <th>{{ __('sale.order.code') }}</th>
                                            <th>{{ __('app.date') }}</th>
                                            <th>{{ __('app.due_date') }}</th>
                                            <th>{{ __('customer.customer') }}</th>
                                            <th>{{ __('app.total') }}</th>
                                            <th>{{ __('app.created_by') }}</th>
                                            <th>{{ __('app.created_at') }}</th>
                                            <th>{{ __('app.action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </form>

							{{-- Pagination if needed --}}
							{{-- <div class="d-flex justify-content-end mt-3">
								{{ $sales->links() }}
							</div> --}}
						</div>
					</div>
				</div>
            @endcan
			</div>
		</div>
		<!--end page wrapper -->
		@endsection

@section('js')
<script src="{{ global_asset('custom/js/dashboard.js') }}"></script>
<script src="{{ global_asset('custom/js/custom.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('custom/js/sale/dashboard-sale-order-list.js') }}"></script>

<script>
	/*Bar Chart Data*/
	var chartMonths = @json($saleVsPurchase).map(record => record.label);
	var chartSales = @json($saleVsPurchase).map(record => record.sales);
	var chartPurchases = @json($saleVsPurchase).map(record => record.purchases);

	/*Doughnut Chart Data*/
	var serviceNames = @json($trendingItems).map(x => x.name);
	var serviceCounts = @json($trendingItems).map(x => x.total_quantity);



	// <script>
	$(document).ready(function() {
		$('#lowStockTable').DataTable({
			"pageLength": 5,
			"lengthMenu": [5, 10, 25, 50],
			"order": [[ 3, "desc" ]], // order by Created At
		});
	});

	$(document).ready(function() {
		$('#recentSalesTable').DataTable({
			"pageLength": 5,
			"lengthMenu": [5, 10, 25, 50],
			"order": [[ 3, "desc" ]], // order by Created At
		});
	});



</script>
@endsection
