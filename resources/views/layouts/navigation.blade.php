<!--sidebar wrapper -->
		<div class="sidebar-wrapper" data-simplebar="true">
			<div class="sidebar-header">
				<div>
					<img src={{ url("/app/getimage/" . app('site')['colored_logo']) }} class="logo-icon" alt="logo icon">
				</div>
				<div>
					<h4 class="logo-text">{{ app('site')['name'] }}</h4>
				</div>
				<div class="toggle-icon ms-auto"><i class='bx bx-arrow-back'></i>
				</div>
			 </div>
			<!--navigation-->
			<ul class="metismenu" id="menu">
				<li>
					<a href="/dashboard">
						<div class="parent-icon"><i class='bx bx-home-alt'></i>
						</div>
						<div class="menu-title">{{ __('app.dashboard') }}</div>
					</a>
				</li>

				@canany(['customer.create', 'customer.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-group"></i>
						</div>
						<div class="menu-title">Customer</div>
					</a>
					<ul>

						@can('customer.view')
						<li class="{{ request()->is(['party/customer/*', 'party/payment/customer/*'])? 'mm-active' : '' }}">
						    <a href="{{ route('party.list', ['partyType' => 'customer']) }}"><i class='bx bx-radio-circle'></i>{{ __('customer.customers') }}</a>
						</li>
						@endcan

						{{-- @can('supplier.view') --}}
						<li class="{{ request()->is('party/history/*')? 'mm-active' : '' }}">
						    <a href="{{ route('party.page') }}"><i class='bx bx-radio-circle'></i>{{ __('Customer History') }}</a>
						</li>
						{{-- @endcan --}}
						@can('party.category.view')
						<li class="{{ request()->is('category/*', 'category/*') ? 'mm-active' : '' }}">
								<a href="{{ route('party.category.list') }}"><i class='bx bx-radio-circle'></i>{{ __('Category') }}</a>
							</li>
						@endcan
					</ul>
				</li>
				@endcanany

				@canany([ 'supplier.create', 'supplier.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-group"></i>
						</div>
						<div class="menu-title">Supplier</div>
					</a>
					<ul>

						

						@can('supplier.view')
						<li class="{{ request()->is(['party/supplier/*', 'party/payment/supplier/*'])? 'mm-active' : '' }}">
						    <a href="{{ route('party.list', ['partyType' => 'supplier']) }}"><i class='bx bx-radio-circle'></i>{{ __('supplier.suppliers') }}</a>
						</li>
						@endcan
						
					</ul>
				</li>
				@endcanany

				@canany(['sale.invoice.view', 'sale.order.view', 'sale.return.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-cart"></i>
						</div>
						<div class="menu-title">Order</div>
					</a>
					<ul>
						{{-- @can('sale.invoice.create')
						<li class="{{ request()->is('pos*') ? 'mm-active' : '' }}">
											<a href="{{ route('pos.create') }}"><i class='bx bx-radio-circle'></i>{{ __('sale.pos') }}</a>
										</li>
						@endcan --}}

                        {{-- @can('sale.invoice.view')
						<li class="{{ request()->is('sale/invoice/*') ? 'mm-active' : '' }}">
											<a href="{{ route('sale.invoice.list') }}"><i class='bx bx-radio-circle'></i>{{ __('sale.invoices') }}</a>
										</li>
						@endcan --}}

						{{-- @can('sale.invoice.view')
						<li class="{{ request()->is('payment/in') ? 'mm-active' : '' }}">
											<a href="{{ route('sale.payment.in') }}"><i class='bx bx-radio-circle'></i>{{ __('Payment History') }}</a>
										</li>
						@endcan --}}

						@can('sale.order.view')
						<li class="{{ request()->is('sale/order/*') ? 'mm-active' : '' }}">
											<a href="{{ route('sale.order.list') }}"><i class='bx bx-radio-circle'></i>Order List</a>
										</li>

										<li class="{{ request()->is('sale/order/create') ? 'mm-active' : '' }}">
											<a href="{{ route('sale.order.create') }}"><i class='bx bx-radio-circle'></i>Create Order</a>
										</li>
						@endcan

						{{-- @can('sale.return.view')
						<li class="{{ request()->is('sale/return/*') ? 'mm-active' : '' }}">
											<a href="{{ route('sale.return.list') }}"><i class='bx bx-radio-circle'></i>{{ __('sale.return.return') }}</a>
										</li>
						@endcan --}}
					</ul>
				</li>
				@endcanany




				{{-- @canany(['sale.invoice.view', 'sale.order.view', 'sale.return.view']) --}}
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-cart"></i>
						</div>
						<div class="menu-title">Order Payments</div>
					</a>
					<ul>
						{{-- @can('sale.invoice.create')
						<li class="{{ request()->is('pos*') ? 'mm-active' : '' }}">
											<a href="{{ route('pos.create') }}"><i class='bx bx-radio-circle'></i>{{ __('sale.pos') }}</a>
										</li>
						@endcan --}}

                        {{-- @can('sale.invoice.view') --}}
						<li class="{{ request()->is('sale/invoice/*') ? 'mm-active' : '' }}">
											<a href="{{ route('order-payments.create') }}"><i class='bx bx-radio-circle'></i>Pay Order Payment</a>
										</li>
						{{-- @endcan --}}

						{{-- @can('sale.invoice.view') --}}
						<li class="{{ request()->is('order-payment-history') ? 'mm-active' : '' }}">
											<a href="{{ route('order.payment.history.list') }}"><i class='bx bx-radio-circle'></i>{{ __('Payment History') }}</a>
										</li>
						{{-- @endcan --}}

						

						{{-- @can('sale.return.view') --}}
						<li class="{{ request()->is('sale/return/*') ? 'mm-active' : '' }}">
											<a href="{{ route('sale.return.list') }}"><i class='bx bx-radio-circle'></i>{{ __('sale.return.return') }}</a>
										</li>
						{{-- @endcan --}}
					</ul>
				</li>
				{{-- @endcanany --}}


				

				@canany(['purchase.bill.view', 'purchase.order.view', 'purchase.return.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-purchase-tag-alt"></i>
						</div>
						<div class="menu-title">{{
						__('purchase.purchase') }}</div>
					</a>
					<ul>
                        @can('purchase.bill.view')
						<li class="{{ request()->is('purchase/bill/list/*') ? 'mm-active' : '' }}">
							<a href="{{ route('purchase.bill.list') }}"><i class='bx bx-radio-circle'></i>{{ __('purchase.bills') }}</a>
						</li>
						<li class="{{ request()->is('purchase/bill/create/*') ? 'mm-active' : '' }}">
							<a href="{{ route('purchase.bill.create') }}"><i class='bx bx-radio-circle'></i>{{ __('purchase.create') }}</a>
						</li>
						@endcan

						 {{-- @can('purchase.bill.view')
							<li class="{{ request()->is('purchase/return') ? 'mm-active' : '' }}">
								<a href="{{ route('purchase.return') }}"><i class='bx bx-radio-circle'></i>Purchase Return</a>
							</li>
						@endcan --}}
						@can('purchase.return.view')
						<li class="{{ request()->is('purchase/return/*') ? 'mm-active' : '' }}">
											<a href="{{ route('purchase.return.list') }}"><i class='bx bx-radio-circle'></i>{{ __('purchase.return.return') }}</a>
										</li>
						@endcan

						{{--@can('purchase.order.view')
						<li class="{{ request()->is('purchase/order/*') ? 'mm-active' : '' }}">
											<a href="{{ route('purchase.order.list') }}"><i class='bx bx-radio-circle'></i>{{ __('purchase.order.order') }}</a>
										</li>
						@endcan

						@can('purchase.return.view')
						<li class="{{ request()->is('purchase/return/*') ? 'mm-active' : '' }}">
											<a href="{{ route('purchase.return.list') }}"><i class='bx bx-radio-circle'></i>{{ __('purchase.return.return') }}</a>
										</li>
						@endcan --}}
					</ul>
				</li>
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-cart"></i>
						</div>
						<div class="menu-title">Purchase Payments</div>
					</a>
					<ul>
						

                        {{-- @can('sale.invoice.view') --}}
						<li class="{{ request()->is('/purchase/payment/create') ? 'mm-active' : '' }}">
											<a href="{{ route('purchase.payments.create') }}"><i class='bx bx-radio-circle'></i>Purchase Payment</a>
										</li>
						{{-- @endcan --}}

						{{-- @can('sale.invoice.view') --}}
						{{-- <li class="{{ request()->is('purchase/payment/history') ? 'mm-active' : '' }}">
											<a href="{{ route('purchase.payment.history.list') }}"><i class='bx bx-radio-circle'></i>Payment History'</a>
										</li> --}}
						{{-- @endcan --}}

						

						{{-- @can('sale.return.view') --}}
						<li class="{{ request()->is('sale/return/*') ? 'mm-active' : '' }}">
											<a href="{{ route('sale.return.list') }}"><i class='bx bx-radio-circle'></i>{{ __('sale.return.return') }}</a>
										</li>
						{{-- @endcan --}}
					</ul>
				</li>
				@endcanany

				
				{{-- @canany(['customer.create', 'customer.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-group"></i>
						</div>
						<div class="menu-title">{{
						__('customer.customers') }}</div>
					</a>
					<ul>
						@can('customer.create')
						<li class="{{ request()->is('customer/create') ? 'mm-active' : '' }}">
											<a href="{{ route('customer.create') }}"><i class='bx bx-radio-circle'></i>{{ __('customer.create_customer') }}</a>
										</li>
						@endcan
						@can('customer.view')
						<li class="{{ request()->is('customer/list', 'customer/edit*') ? 'mm-active' : '' }}">
											<a href="{{ route('customer.list') }}"><i class='bx bx-radio-circle'></i>{{ __('customer.list') }}</a>
										</li>
						@endcan
					</ul>
				</li>
				@endcanany --}}
				





				@canany(['item.create', 'item.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-package"></i>
						</div>
						<div class="menu-title">{{
						__('Inventory') }}</div>
					</a>
					<ul>
						@can('item.view')
						<li class="{{ request()->is('item/list', 'item/create', 'item/edit*', 'item/transaction*') ? 'mm-active' : '' }}">
											<a href="{{ route('item.list') }}"><i class='bx bx-radio-circle'></i>{{ __('Inventory') }}</a>
										</li>

						@endcan
						@can('item.category.view')
						<li class="{{ request()->is('item/category/*', 'item/category/*') ? 'mm-active' : '' }}">
											<a href="{{ route('item.category.list') }}"><i class='bx bx-radio-circle'></i>{{ __('item.category.list') }}</a>
										</li>
						@endcan
					</ul>
				</li>
				@endcanany

				

				@canany(['expense.create', 'expense.view', 'expense.category.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-minus-circle"></i>
						</div>
						<div class="menu-title">{{
						__('expense.expense') }}</div>
					</a>
					<ul>
						@can('expense.view')
						<li class="{{ request()->is('expense/list', 'expense/create','expense/edit*', 'expense/print/*') ? 'mm-active' : '' }}">
											<a href="{{ route('expense.list') }}"><i class='bx bx-radio-circle'></i>{{ __('Daily Expense') }}</a>
										</li>

										<li class="{{ request()->is('expense/list', 'expense/create','expense/edit*', 'expense/print/*') ? 'mm-active' : '' }}">
											<a href="{{ route('expense.total') }}"><i class='bx bx-radio-circle'></i>{{ __('Cash Out') }}</a>
										</li>

										<li class="{{ request()->is('expense/list', 'expense/create','expense/edit*', 'expense/print/*') ? 'mm-active' : '' }}">
											<a href="{{ route('expense.cash_in_page') }}"><i class='bx bx-radio-circle'></i>{{ __('Cash In') }}</a>
										</li>
						@endcan
						@can('expense.category.view')
						<li class="{{ request()->is('expense/category/list', 'expense/category/edit*') ? 'mm-active' : '' }}">
											<a href="{{ route('expense.category.list') }}"><i class='bx bx-radio-circle'></i>{{ __('expense.category.list') }}</a>
										</li>
						@endcan
					</ul>
				</li>
				@endcanany

				
				<li class="menu-label">CORE</li>
				@canany(['transaction.cash.view', 'transaction.cheque.view', 'transaction.bank.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-wallet-alt"></i>
						</div>
						<div class="menu-title">{{
						__('payment.cash_and_bank') }}</div>
					</a>
					<ul>
						@can('transaction.cash.view')
						<li class="{{ request()->is('transaction/cash/list') ? 'mm-active' : '' }}">
											<a href="{{ route('transaction.cash.list') }}"><i class='bx bx-radio-circle'></i>{{ __('payment.cash_in_hand') }}</a>
										</li>
						@endcan

						@can('transaction.cheque.view')
						<li class="{{ request()->is('transaction/cheque/list') ? 'mm-active' : '' }}">
											<a href="{{ route('transaction.cheque.list') }}"><i class='bx bx-radio-circle'></i>{{ __('payment.cheques') }}</a>
										</li>
						@endcan

						@can('transaction.bank.view')
						<li class="{{ request()->is('transaction/bank/list') ? 'mm-active' : '' }}">
											<a href="{{ route('transaction.bank.list') }}"><i class='bx bx-radio-circle'></i>{{ __('payment.bank') }}</a>
										</li>
						@endcan
					</ul>
				</li>
				@endcanany

				@canany(['warehouse.view', 'stock_transfer.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-building"></i>
						</div>
						<div class="menu-title">{{
						__('warehouse.warehouse') }}</div>
					</a>
					<ul>
						@can('warehouse.view')
						<li class="{{ request()->is('warehouse*') ? 'mm-active' : '' }}">
							<a href="{{ route('warehouse.list') }}"><i class='bx bx-radio-circle'></i>{{ __('warehouse.warehouses') }}</a>
						</li>
						@endcan

						@can('stock_transfer.view')
						<li class="{{ request()->is('stock-transfer/list') ? 'mm-active' : '' }}">
											<a href="{{ route('stock_transfer.list') }}"><i class='bx bx-radio-circle'></i>{{ __('warehouse.stock_transfer') }}</a>
										</li>
						@endcan


					</ul>
				</li>
				@endcanany

				

				@canany(['account.create', 'account.view', 'account.group.create', 'account.group.view'])
				<li class="d-none">
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-money"></i>
						</div>
						<div class="menu-title">{{
						__('account.accounts') }}</div>
					</a>
					<ul>
						@can('account.view')
						<li class="{{ request()->is('account/list', 'account/create', 'account/edit*') ? 'mm-active' : '' }}">
											<a href="{{ route('account.list') }}"><i class='bx bx-radio-circle'></i>{{ __('account.list') }}</a>
										</li>

						@endcan
						@can('account.group.view')
						<li class="{{ request()->is('account/group/list', 'account/group/create', 'account/group/edit*') ? 'mm-active' : '' }}">
											<a href="{{ route('account.group.list') }}"><i class='bx bx-radio-circle'></i>{{ __('account.group.list') }}</a>
										</li>
						@endcan
					</ul>
				</li>
				@endcanany

				@canany(['profile.edit', 'user.view', 'role.view', 'permission.view', 'permission.group.view'])
                <li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-group"></i>
						</div>
						<div class="menu-title">{{ __('user.users') }}</div>
					</a>
					<ul>
						@can('profile.edit')
						<li class="{{ request()->is('profile') ? 'mm-active' : '' }}">
											<a href="{{ route('user.profile') }}"><i class='bx bx-radio-circle'></i>{{ __('user.profile') }}</a>
										</li>
						@endcan
						@can('user.view')
						<li class="{{ request()->is('users*') ? 'mm-active' : '' }}">
											<a href="{{ route('users.list') }}"><i class='bx bx-radio-circle'></i>{{ __('user.users') }}</a>
										</li>
						@endcan
						@can('role.view')
                        <li class="{{ request()->is('role-and-permission/role*') ? 'mm-active' : '' }}">
							<a href="{{ route('roles.list') }}"><i class='bx bx-radio-circle'></i>{{ __('app.roles') }}</a>
						</li>
						@endcan
					</ul>
					@canany(['permission.view', 'permission.group.view'])
					{{--<ul>
						<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('app.permissions') }}</a>

									<ul>
										@can('permission.view')
										<li class="{{ request()->is('role-and-permission/permission*') ? 'mm-active' : '' }}">
											<a href="{{ route('permission.list') }}"><i class='bx bx-radio-circle'></i>{{ __('app.permission_list') }}</a>
										</li>
										@endcan
										@can('permission.group.view')
										<li class="{{ request()->is('role-and-permission/group*') ? 'mm-active' : '' }}">
											<a href="{{ route('permission.group.list') }}"><i class='bx bx-radio-circle'></i>{{ __('app.group_list') }}</a>
										</li>
										@endcan
									</ul>

						</li>
					</ul> --}}
					@endcanany
				</li>
				@endcanany

				

                @canany(['report.*'])
                <li>
                    <a href="javascript:;" class="has-arrow">
                        <div class="parent-icon"><i class="bx bx-bar-chart-square"></i>
                        </div>
                        <div class="menu-title">{{
                        __('app.reports') }}</div>
                    </a>
                    <ul>
                    	{{-- @can('report.reorder.item')
                        <li class="{{ request()->is('report/profit-and-loss') ? 'mm-active' : '' }}">
                                            <a href="{{ route('report.profit_and_loss') }}"><i class='bx bx-radio-circle'></i>{{ __('account.profit_and_loss') }}</a>
                                        </li>
                        @endcan --}}
                    	{{--
                    	@canany(['report.balance_sheet', 'report.trial_balance'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('account.accounting') }}</a>

										<ul>
											@can('report.balance_sheet')
											<li class="{{ request()->is('report/balance-sheet') ? 'mm-active' : '' }}">
												<a href="{{ route('report.balance_sheet') }}"><i class='bx bx-radio-circle'></i>{{ __('account.balance_sheet') }}</a>
											</li>
											@endcan
											@can('report.trial_balance')
											<li class="{{ request()->is('report/trial-balance') ? 'mm-active' : '' }}">
												<a href="{{ route('report.trial_balance') }}"><i class='bx bx-radio-circle'></i>{{ __('account.trial_balance') }}</a>
											</li>
											@endcan
										</ul>

							</li>
						@endcanany
						--}}
						{{-- @canany(['report.item.transaction.batch', 'report.item.transaction.serial', 'report.item.transaction.general'])

							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('item.item_transaction') }}</a>
										<ul>
											@can('report.item.transaction.batch')
											<li class="{{ request()->is('report/item-transaction/batch') ? 'mm-active' : '' }}">
												<a href="{{ route('report.item.transaction.batch') }}"><i class='bx bx-radio-circle'></i>{{ __('item.batch_wise') }}</a>
											</li>
											@endcan
											@can('report.item.transaction.serial')
											<li class="{{ request()->is('report/item-transaction/serial') ? 'mm-active' : '' }}">
												<a href="{{ route('report.item.transaction.serial') }}"><i class='bx bx-radio-circle'></i>{{ __('item.serial_or_imei') }}</a>
											</li>
											@endcan
                                            @can('report.item.transaction.general')
                                            <li class="{{ request()->is('report/item-transaction/general') ? 'mm-active' : '' }}">
                                                <a href="{{ route('report.item.transaction.general') }}"><i class='bx bx-radio-circle'></i>{{ __('app.general') }}</a>
                                            </li>
                                            @endcan
										</ul>
							</li>

						@endcanany
						@canany(['report.purchase', 'report.purchase.item'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('purchase.purchase') }}</a>
										<ul>
											@can('report.purchase')
											<li class="{{ request()->is('report/purchase') ? 'mm-active' : '' }}">
												<a href="{{ route('report.purchase') }}"><i class='bx bx-radio-circle'></i>{{ __('purchase.purchase') }}</a>
											</li>
											@endcan
											@can('report.purchase.item')
											<li class="{{ request()->is('report/purchase/item') ? 'mm-active' : '' }}">
												<a href="{{ route('report.purchase.item') }}"><i class='bx bx-radio-circle'></i>{{ __('purchase.item_purchase') }}</a>
											</li>
											@endcan
											@can('report.purchase.payment')
											<li class="{{ request()->is('report/purchase/payment') ? 'mm-active' : '' }}">
												<a href="{{ route('report.purchase.payment') }}"><i class='bx bx-radio-circle'></i>{{ __('app.payment') }}</a>
											</li>
											@endcan
										</ul>
							</li>
						@endcanany --}}
						@canany(['report.sale', 'report.sale.item'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('sale.sale') }}</a>
										<ul>
											@can('report.sale')
											<li class="{{ request()->is('report/sale') ? 'mm-active' : '' }}">
												<a href="{{ route('report.sale') }}"><i class='bx bx-radio-circle'></i>{{ __('Customer Sale') }}</a>
											</li>
											@endcan
											@can('report.sale.item')
											<li class="{{ request()->is('report/sale/item') ? 'mm-active' : '' }}">
												<a href="{{ route('report.sale.item') }}"><i class='bx bx-radio-circle'></i>{{ __('sale.item_sale') }}</a>
											</li>
											@endcan
											@can('report.sale.payment')
											<li class="{{ request()->is('report/sale/payment') ? 'mm-active' : '' }}">
												<a href="{{ route('report.sale.payment') }}"><i class='bx bx-radio-circle'></i>{{ __('Total Sales') }}</a>
											</li>
											@endcan
											@can('report.sale.payment')
											<li class="{{ request()->is('report/customer-total') ? 'mm-active' : '' }}">
												<a href="{{ route('report.customer-total.page') }}"><i class='bx bx-radio-circle'></i>{{ __('Customer Total') }}</a>
											</li>
											@endcan
										</ul>
							</li>
						@endcanany

						@canany(['report.customer.due.payment', 'report.supplier.due.payment'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('payment.due_payments') }}&nbsp;<span class="badge bg-primary">New</span></a>
										<ul>
											@can('report.customer.due.payment')
											<li class="{{ request()->is('report/customer/due') ? 'mm-active' : '' }}">
												<a href="{{ route('report.customer.due.payment') }}"><i class='bx bx-radio-circle'></i>{{ __('customer.customer') }}</a>
											</li>
											@endcan
											{{-- @can('report.supplier.due.payment')
											<li class="{{ request()->is('report/supplier/due') ? 'mm-active' : '' }}">
												<a href="{{ route('report.supplier.due.payment') }}"><i class='bx bx-radio-circle'></i>{{ __('supplier.supplier') }}</a>
											</li>
											@endcan --}}
										</ul>
							</li>
						@endcanany
						@can('report.sale.payment')
						<li class="{{ request()->is('report/stock/page') ? 'mm-active' : '' }}">
							<a href="{{ route('report.stock.page') }}"><i class='bx bx-radio-circle'></i>{{ __('Stock Report') }}</a>
						</li>
						@endcan

						@can('report.sale.payment')
						<li class="{{ request()->is('report/cashin/page') ? 'mm-active' : '' }}">
							<a href="{{ route('report.cashin.page') }}"><i class='bx bx-radio-circle'></i>{{ __('Cash-In Report') }}</a>
						</li>
						@endcan

						@can('report.sale.payment')
						<li class="{{ request()->is('report/customer-ledger') ? 'mm-active' : '' }}">
							<a href="{{ route('report.customer.ledger.page') }}"><i class='bx bx-radio-circle'></i>{{ __('Customer Ledger') }}</a>
						</li>
						@endcan

						@can('report.sale.payment')
						<li class="{{ request()->is('report/daily') ? 'mm-active' : '' }}">
							<a href="{{ route('report.daily.page') }}"><i class='bx bx-radio-circle'></i>{{ __('Daily Report') }}</a>
						</li>
						@endcan

						{{-- @canany(['report.expense', 'report.expense.item'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('expense.expense') }}</a>
										<ul>
											@can('report.expense')
											<li class="{{ request()->is('report/expense') ? 'mm-active' : '' }}">
												<a href="{{ route('report.expense') }}"><i class='bx bx-radio-circle'></i>{{ __('expense.expense') }}</a>
											</li>
											@endcan
											@can('report.expense.item')
											<li class="{{ request()->is('report/expense/item') ? 'mm-active' : '' }}">
												<a href="{{ route('report.expense.item') }}"><i class='bx bx-radio-circle'></i>{{ __('expense.item') }}</a>
											</li>
											@endcan
											@can('report.expense.payment')
											<li class="{{ request()->is('report/expense/payment') ? 'mm-active' : '' }}">
												<a href="{{ route('report.expense.payment') }}"><i class='bx bx-radio-circle'></i>{{ __('app.payment') }}</a>
											</li>
											@endcan
										</ul>
							</li>
						@endcanany --}}

						{{-- @canany(['report.transaction.cashflow', 'report.transaction.bank-statement'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('app.transactions') }}</a>
										<ul>
											@can('report.transaction.cashflow')
											<li class="{{ request()->is('report/transaction/cashflow') ? 'mm-active' : '' }}">
												<a href="{{ route('report.transaction.cashflow') }}"><i class='bx bx-radio-circle'></i>{{ __('payment.cash_flow') }}</a>
											</li>
											@endcan
											@can('report.transaction.bank-statement')
											<li class="{{ request()->is('report/transaction/bank-statement') ? 'mm-active' : '' }}">
												<a href="{{ route('report.transaction.bank-statement') }}"><i class='bx bx-radio-circle'></i>{{ __('payment.bank_statement') }}</a>
											</li>
											@endcan
										</ul>
							</li>
						@endcanany

						@canany(['report.gst*'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('item.gst') }}</a>
										<ul>
											@can('report.gstr1')
											<li class="{{ request()->is('report/gstr-1') ? 'mm-active' : '' }}">
												<a href="{{ route('report.gstr-1') }}"><i class='bx bx-radio-circle'></i>{{ __('item.gstr-1') }}</a>
											</li>
											@endcan
											@can('report.gstr2')
											<li class="{{ request()->is('report/gstr-2') ? 'mm-active' : '' }}">
												<a href="{{ route('report.gstr-2') }}"><i class='bx bx-radio-circle'></i>{{ __('item.gstr-2') }}</a>
											</li>
											@endcan
										</ul>
							</li>
						@endcanany
						@canany(['report.stock_transfer', 'report.stock_transfer.item'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('warehouse.stock_transfer') }}</a>
										<ul>
											@can('report.stock_transfer')
											<li class="{{ request()->is('report/stock-transfer') ? 'mm-active' : '' }}">
												<a href="{{ route('report.stock_transfer') }}"><i class='bx bx-radio-circle'></i>{{ __('warehouse.stock_transfer') }}</a>
											</li>
											@endcan
											@can('report.stock_transfer.item')
											<li class="{{ request()->is('report/stock-transfer/item') ? 'mm-active' : '' }}">
												<a href="{{ route('report.stock_transfer.item') }}"><i class='bx bx-radio-circle'></i>{{ __('item.item_wise') }}</a>
											</li>
											@endcan
										</ul>
							</li>
						@endcanany

						@canany(['report.stock_report.*'])
							<li> <a class="has-arrow" href="javascript:;"><i class='bx bx-radio-circle'></i>{{ __('item.stock_report') }}&nbsp;<span class="badge bg-primary">New</span></a>
										<ul>
											@can('report.stock_report.item.batch')
											<li class="{{ request()->is('report/stock-report/batch') ? 'mm-active' : '' }}">
												<a href="{{ route('report.stock_report.item.batch') }}"><i class='bx bx-radio-circle'></i>{{ __('item.batch_wise') }}</a>
											</li>
											@endcan
											@can('report.stock_report.item.serial')
											<li class="{{ request()->is('report/stock-report/serial') ? 'mm-active' : '' }}">
												<a href="{{ route('report.stock_report.item.serial') }}"><i class='bx bx-radio-circle'></i>{{ __('item.serial_or_imei') }}</a>
											</li>
											@endcan
                                            @can('report.stock_report.item.general')
                                            <li class="{{ request()->is('report/stock-report/general') ? 'mm-active' : '' }}">
                                                <a href="{{ route('report.stock_report.item.general') }}"><i class='bx bx-radio-circle'></i>{{ __('app.general') }}</a>
                                            </li>
                                            @endcan
										</ul>
							</li>
						@endcanany

						@can('report.expired.item')
                        <li class="{{ request()->is('report/expired/item') ? 'mm-active' : '' }}">
                                            <a href="{{ route('report.expired.item') }}"><i class='bx bx-radio-circle'></i>{{ __('item.expired_item_report') }}</a>
                                        </li>
                        @endcan
                        @can('report.reorder.item')
                        <li class="{{ request()->is('report/reorder/item') ? 'mm-active' : '' }}">
                                            <a href="{{ route('report.reorder.item') }}"><i class='bx bx-radio-circle'></i>{{ __('item.reorder_item_report') }}</a>
                                        </li>
                        @endcan



                        @if(app('company')['is_enable_crm'])
	                        @can('report.order')
	                        <li class="{{ request()->is('report/order') ? 'mm-active' : '' }}">
	                                            <a href="{{ route('report.order') }}"><i class='bx bx-radio-circle'></i>{{ __('order.report') }}</a>
	                                        </li>
	                        @endcan
	                        @can('report.order.payment')
	                        <li class="{{ request()->is('report/order/payment') ? 'mm-active' : '' }}">
	                                            <a href="{{ route('report.order.payment') }}"><i class='bx bx-radio-circle'></i>{{ __('order.payments') }}</a>
	                                        </li>
	                        @endcan
	                        @can('report.job.status')
	                        <li class="{{ request()->is('report/job-status') ? 'mm-active' : '' }}">
	                                            <a href="{{ route('report.job.status') }}"><i class='bx bx-radio-circle'></i>{{ __('order.job-status') }}</a>
	                                        </li>
	                        @endcan
	                    @endif --}}

                    </ul>
                </li>
                @endcanany

				@canany(['tax.view', 'app.settings.edit', 'company.edit', 'payment.type.view', 'unit.view', 'language.view'])
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class="bx bx-cog"></i>
						</div>
						<div class="menu-title">{{ __('app.settings') }}</div>
					</a>
					@canany(['app.settings.edit'])
					<ul>
						<li class="{{ request()->is('settings/app') ? 'mm-active' : '' }}">
							<a href="{{ route('settings.app') }}"><i class='bx bx-radio-circle'></i>{{ __('app.app_settings') }}</a>
						</li>
					</ul>
					@endcanany
					@canany(['company.edit'])
					<ul>
						<li class="{{ request()->is('company') ? 'mm-active' : '' }}">
							<a href="{{ route('company') }}"><i class='bx bx-radio-circle'></i>{{ __('app.company') }}</a>
						</li>
					</ul>
					@endcanany
					@canany(['tax.view'])
					<ul>
						<li class="{{ request()->is('tax*') ? 'mm-active' : '' }}">
							<a href="{{ route('tax.list') }}"><i class='bx bx-radio-circle'></i>{{ __('tax.tax_rates') }}</a>
						</li>
					</ul>
					@endcanany

					@canany(['payment.type.view'])
					<ul>
						<li class="{{ request()->is('payment*') ? 'mm-active' : '' }}">
							<a href="{{ route('payment.types.list') }}"><i class='bx bx-radio-circle'></i>{{ __('payment.bank_accounts') }}</a>
						</li>
					</ul>
					@endcanany
					@canany(['unit.view'])
					<ul>
						<li class="{{ request()->is('unit*') ? 'mm-active' : '' }}">
							<a href="{{ route('unit.list') }}"><i class='bx bx-radio-circle'></i>{{ __('unit.list') }}</a>
						</li>
					</ul>
					@endcanany
					{{-- @canany(['language.view'])
					<ul>
						<li class="{{ request()->is('language*') ? 'mm-active' : '' }}">
							<a href="{{ route('language.list') }}"><i class='bx bx-radio-circle'></i>{{ __('language.languages') }}</a>
						</li>
					</ul>
					@endcanany --}}
				</li>
				@endcanany

                <li class="menu-label">OTHER</li>

                <li class="bg-light">
					<a href="javascript:void(0);" id="clearCache">
						<div class="parent-icon text-primary"><i class='bx bx-refresh '></i>
						</div>
						<div class="menu-title">{{ __('app.clear_cache') }}</div>
					</a>
				</li>

			</ul>
			<!--end navigation-->
		</div>
		<!--end sidebar wrapper -->
