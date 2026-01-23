"use strict";

let currentPage = 0; // Initialize the page number
let isLoading = false; // Track loading state
let startFromFirst = 0;


// Infinite scroll event listener
/*window.addEventListener('scroll', () => {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight && !isLoading) {
        isLoading = true;
        loadMoreItems(); // Load more items when the user reaches the bottom
    }
});*/


$("#loadMoreBtn").on('click', function(){
    loadMoreItems();
});
$("#item_category_id, #warehouse_id, #party_id").on('change', function(){
    currentPage = 0;
    startFromFirst = 0;
    loadMoreItems();
});

function loadMoreItems() {
    currentPage++; // Increment the page number
    $.ajax({
        url: baseURL + '/item/ajax/pos/item-grid/get-list',
        method: 'GET',
        data: {
            search: $('#search_item').val(), // Pass the search term if necessary
            page: currentPage,
            item_category_id : $("#item_category_id").val(),
            warehouse_id : $("#warehouse_id").val(),
            party_id : $("#party_id").val(),
        },
        beforeSend: function() {
          showLoadingMessage(); // Show the loading message
        },
        success: function (response) {
            if (response.length > 0) {
                var jsonObject = response;//JSON.parse(response);
                jsonObject.forEach(item => {
                    appendItemToGrid(item); // Function to append item to the grid
                });
                hideLoadingMessage();
            }else{
                if(startFromFirst == 0){
                    $('#itemsGrid').html('');
                }
                noMoreData();
            }
            isLoading = false; // Reset loading state
        },
        error: function () {
            isLoading = false; // Reset loading state on error
        },
        complete: function() {
          
        },
    });
}

function noMoreData(){
    loadMoreBtn.textContent = 'No More Data';
    loadMoreBtn.disabled = true; // Re-enable the button on error
    hideSpinner();
}

function showLoadingMessage() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    loadMoreBtn.textContent = 'Loading...';
    loadMoreBtn.disabled = true;
    showSpinner();
}

function hideLoadingMessage() {
    loadMoreBtn.textContent = 'Load More';
    loadMoreBtn.disabled = false; // Re-enable the button
    hideSpinner();
}

function appendItemToGrid(item) {
    
    var image_path = baseURL + '/item/getimage/thumbnail/' + item.image_path;

    const itemHtml = `
        <div class="col">
            <div class="card h-100 item-card border">
                <div class="item-image">
                    <img src="${image_path}" class="card-img-top" alt="${item.name}">
                    <span class="item-quantity">Qty: ${_parseQuantity(item.current_stock)}</span>
                </div>
                <div class="card-body">
                    <h6 class="card-title">${item.name}</h6>
                    <p class="card-text item-price">${_parseFix(item.sale_price)}</p>
                </div>
                <div class="add-item position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none justify-content-center align-items-center rounded">
                    <button class="btn btn-primary" type="button" onclick='addItemToGrid(${JSON.stringify(item)})'>+</button>
                </div>
            </div>
        </div>
    `;
    if(startFromFirst == 0){
        startFromFirst++;
        $('#itemsGrid').html('');    
    }
    $('#itemsGrid').append(itemHtml); // Append the item HTML to the grid
}

function addItemToGrid(item) {
    // Prepare the data object as per your structure
    var dataObject = {
        warehouse_id: item.warehouse_id,
        id: item.id,
        name: item.name,
        tracking_type: item.tracking_type,
        description: item.description,
        sale_price: item.sale_price,
        is_sale_price_with_tax: item.is_sale_price_with_tax,
        tax_id: item.tax_id,
        quantity: _parseQuantity(item.quantity),
        taxList: item.taxList,
        unitList: item.unitList,
        base_unit_id: item.base_unit_id,
        secondary_unit_id: item.secondary_unit_id,
        selected_unit_id: item.selected_unit_id,
        conversion_rate: item.conversion_rate,
        sale_price_discount: item.sale_price_discount,
        discount_type: item.sale_price_discount_type,
        discount_amount: item.discount_amount,
        total_price_after_discount: item.total_price_after_discount,
        tax_amount: item.tax_amount,
        total_price: item.total_price,
        serial_numbers: (item.tracking_type === 'serial') ? JSON.stringify(item.serial_numbers) : '',
        batch_no: (item.tracking_type === 'batch') ? item.batch_no : '',
        mfg_date: (item.tracking_type === 'batch') ? item.mfg_date : '',
        exp_date: (item.tracking_type === 'batch') ? item.exp_date : '',
        mrp: item.mrp,
        
        model_no: (item.tracking_type === 'batch') ? item.model_no : '',
        color: (item.tracking_type === 'batch') ? item.color : '',
        size: (item.tracking_type === 'batch') ? item.size : '',
    };
    addRowToInvoiceItemsTable(dataObject, false); // Add row to table
}

jQuery(document).ready(function($) {
    loadMoreItems();
});
