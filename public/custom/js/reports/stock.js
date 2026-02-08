$(function() {
    "use strict";

    let originalButtonText;

    const tableId = $('#stockReport');

    /**
     * Language
     * */
    const _lang = {
                total : "Total",
                noRecordsFound : "No Records Found!!",
            };

    $("#stockReportForm").on("submit", function(e) {
        e.preventDefault();
        const form = $(this);
        const formArray = {
            formId: form.attr("id"),
            csrf: form.find('input[name="_token"]').val(),
            url: form.closest('form').attr('action'),
            formObject : form,
        };
        ajaxRequest(formArray);
    });


    function disableSubmitButton(form) {
        originalButtonText = form.find('button[type="submit"]').text();
        form.find('button[type="submit"]')
            .prop('disabled', true)
            .html('  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...');
    }

    function enableSubmitButton(form) {
        form.find('button[type="submit"]')
            .prop('disabled', false)
            .html(originalButtonText);
    }

    function beforeCallAjaxRequest(formObject){
        disableSubmitButton(formObject);
        showSpinner();
    }
    function afterCallAjaxResponse(formObject){
        enableSubmitButton(formObject);
        hideSpinner();
    }
    let stockResponse = null;

    function afterSeccessOfAjaxRequest(formObject, response){
        formAdjustIfSaveOperation(response);
        stockResponse = response; // store for PDF export
    }
    function afterFailOfAjaxRequest(formObject){
        showNoRecordsMessageOnTableBody();
    }

    function ajaxRequest(formArray){
        var formData = new FormData(document.getElementById(formArray.formId));
        var jqxhr = $.ajax({
            type: 'POST',
            url: formArray.url,
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': formArray.csrf
            },
            beforeSend: function() {
                // Actions to be performed before sending the AJAX request
                if (typeof beforeCallAjaxRequest === 'function') {
                    beforeCallAjaxRequest(formArray.formObject);
                }
            },
        });
        jqxhr.done(function(response) {
            // Actions to be performed after response from the AJAX request
            if (typeof afterSeccessOfAjaxRequest === 'function') {
                afterSeccessOfAjaxRequest(formArray.formObject, response);
            }
        });
        jqxhr.fail(function(response) {
            var message = response.responseJSON.message;
            iziToast.error({title: 'Error', layout: 2, message: message});
            if (typeof afterFailOfAjaxRequest === 'function') {
                afterFailOfAjaxRequest(formArray.formObject);
            }
        });
        jqxhr.always(function() {
            // Actions to be performed after the AJAX request is completed, regardless of success or failure
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray.formObject);
            }
        });
    }

    // function formAdjustIfSaveOperation(response){
    //     var tableBody = tableId.find('tbody');

    //     var id = 1;
    //     var tr = "";
        
    //     var totalQuantity = parseFloat(0);
    //     var totalSum = parseFloat(0);
    //     var totalDiscountAmount = parseFloat(0);
    //     var totalTaxAmount = parseFloat(0);
        
    //     $.each(response.data, function(index, item) {
    //         totalQuantity += parseFloat(item.quantity);
    //         totalDiscountAmount += parseFloat(item.discount_amount);
    //         totalTaxAmount += parseFloat(item.tax_amount);
    //         totalSum += parseFloat(item.total);

    //         tr  +=`
    //             <tr>
    //                 <td>${id++}</td>
    //                 <td>${item.sale_date}</td>
    //                 <td>${item.invoice_or_bill_code}</td>
    //                 <td>${item.party_name}</td>
    //                 <td>${item.warehouse}</td>
    //                 <td>${item.item_name}</td>
    //                 <td class='text-end' data-tableexport-celltype="number" >${_formatNumber(item.unit_price)}</td>
    //                 <td class='text-end' data-tableexport-celltype="number" >${_formatNumber(item.quantity)}</td>
    //                 <td class='text-end' data-tableexport-celltype="number" >${_formatNumber(item.discount_amount)}</td>
    //                 <td class='text-end ${noTaxFlag()?'d-none':''}' data-tableexport-celltype="number" >${_formatNumber(item.tax_amount)}</td>
    //                 <td class='text-end' data-tableexport-celltype="number" >${_formatNumber(item.total)}</td>
    //             </tr>
    //         `;
    //     });

    //     tr  +=`
    //         <tr class='fw-bold'>
    //             <td colspan='0' class='text-end tfoot-first-td'>${_lang.total}</td>
    //             <td class='text-end' data-tableexport-celltype="number">${_formatNumber(totalQuantity)}</td>
    //             <td class='text-end' data-tableexport-celltype="number">${_formatNumber(totalDiscountAmount)}</td>
    //             <td class='text-end ${noTaxFlag()?'d-none':''}' data-tableexport-celltype="number">${_formatNumber(totalTaxAmount)}</td>
    //             <td class='text-end' data-tableexport-celltype="number">${_formatNumber(totalSum)}</td>
    //         </tr>
    //     `;

    //     // Clear existing rows:
    //     tableBody.empty();
    //     tableBody.append(tr);

    //     /**
    //      * Set colspan of the table bottom
    //      * */
    //     $('.tfoot-first-td').attr('colspan', columnCountWithoutDNoneClass(4-noTaxFlag()));
    // }
//     function formAdjustIfSaveOperation(response){
//     const tableBody = tableId.find('tbody');
//     let id = 1;
//     let tr = "";

//     let totalQuantity = 0;

//     $.each(response.data, function(index, item) {
//         const quantity = parseFloat(item.total_quantity_sold);
//         totalQuantity += quantity;

//         // tr += `
//         //     <tr>
//         //         <td>${id++}</td>
//         //         <td>${item.item_name}</td>
//         //         <td class='text-start' data-tableexport-celltype="number">${_formatNumber(quantity)}</td>
//         //     </tr>
//         // `;
//          tr += `
//             <tr>
//                 <td>${id++}</td>
//                 <td>${date}</td>
//                 <td>${row.category_name}</td>
//                 <td>${row.item_name}</td>
//                 <td class="text-start" data-tableexport-celltype="number">
//                     ${_formatNumber(row.qty)}
//                 </td>
//             </tr>
//         `;
//     });

//     // Totals Row
//     tr += `
//         <tr class='fw-bold'>
//             <td class='text-end tfoot-first-td' colspan="2">${_lang.total}</td>
//             <td class='text-end' data-tableexport-celltype="number">${_formatNumber(totalQuantity)}</td>
//         </tr>
//     `;

//     tableBody.empty().append(tr);

//     // Set correct colspan (3 columns: #, Item Name, Quantity)
//     $('.tfoot-first-td').attr('colspan', 2);
// }

// function formAdjustIfSaveOperation(response){
//     const tableBody = tableId.find('tbody');
//     let id = 1;
//     let tr = "";

//     let totalQuantity = 0;

//     // Outer loop: date
//     $.each(response.data, function(date, items) {

//         // Inner loop: items for that date
//         $.each(items, function(itemId, row) {

//             const quantity = parseFloat(row.qty);
//             totalQuantity += quantity;

//             tr += `
//                 <tr>
//                     <td>${id++}</td>
//                     <td>${date}</td>
//                     <td>${row.category_name}</td>
//                     <td>${row.item_name}</td>
//                     <td class="text-start" data-tableexport-celltype="number">
//                         ${_formatNumber(quantity)}
//                     </td>
//                 </tr>
//             `;
//         });
//     });

//     // Totals Row
//     tr += `
//         <tr class='fw-bold'>
//             <td colspan="4" class='text-end tfoot-first-td'>${_lang.total}</td>
//             <td class='text-start' data-tableexport-celltype="number">${_formatNumber(totalQuantity)}</td>
//         </tr>
//     `;

//     tableBody.empty().append(tr);
// }


function formAdjustIfSaveOperation(response){
    const tableBody = tableId.find('tbody');
    let id = 1;
    let tr = "";

    let totalRemainingQty = 0;
    let totalStockValue = 0;

    $.each(response.data, function(index, item) {
        const remainingQty = parseFloat(item.remaining_qty);
        const purchasePrice = parseFloat(item.purchase_price);
        const stockValue = parseFloat(item.stock_value);

        totalRemainingQty += remainingQty;
        totalStockValue += stockValue;

        tr += `
            <tr>
                <td>${id++}</td>
                <td>${item.item_name}</td>
                <td class="text-end">${_formatNumber(remainingQty)}</td>
                <td class="text-end">${_formatNumber(purchasePrice)}</td>
                <td class="text-end">${_formatNumber(stockValue)}</td>
            </tr>
        `;
    });

    // Totals Row
    tr += `
        <tr class="fw-bold">
            <td colspan="2" class="text-end">Total</td>
            <td class="text-end">${_formatNumber(totalRemainingQty)}</td>
            <td></td>
            <td class="text-end">${_formatNumber(totalStockValue)}</td>
        </tr>
    `;

    tableBody.empty().append(tr);
}





    // function showNoRecordsMessageOnTableBody() {
    //     var tableBody = tableId.find('tbody');

    //     var tr = "<tr class='fw-bold'>";
    //     tr += `<td colspan='0' class='text-end tfoot-first-td text-center'>${_lang.noRecordsFound}</td>"`;
    //     tr += "</tr>";

    //     tableBody.empty();
    //     tableBody.append(tr);

    //     /**
    //      * Set colspan of the table bottom
    //      * */
    //     $('.tfoot-first-td').attr('colspan', columnCountWithoutDNoneClass(0));
    // }
    function showNoRecordsMessageOnTableBody() {
    const tableBody = tableId.find('tbody');

    const tr = `
        <tr class='fw-bold'>
            <td colspan='3' class='text-center'>${_lang.noRecordsFound}</td>
        </tr>
    `;

    tableBody.empty().append(tr);
}

    function columnCountWithoutDNoneClass(minusCount) {
        
        return tableId.find('thead > tr:first > th').not('.d-none').length - minusCount;
    }

    /** 
     * 
     * Table Exporter
     * PDF, SpreadSheet
     * */
    $(document).on("click", '#generate_pdf', function() {
        if (!stockResponse || !stockResponse.data.length) {
            alert("No data to export");
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');

        let startY = 15;
        const pageWidth = doc.internal.pageSize.getWidth();

        // COMPANY NAME
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Shahid Traders", pageWidth / 2, startY, { align: "center" });

        startY += 8;

        // REPORT TITLE
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        doc.text("Stock Report", pageWidth / 2, startY, { align: "center" });

        startY += 10;

        // FILTER INFO (Category or All)
        const categoryText = $('#item_category_id option:selected').text() || '';
        if ($('#all').is(':checked')) {
            doc.setFontSize(10);
            doc.text(`All Stock Report`, 14, startY);
            startY += 6;
        } else if (categoryText) {
            doc.setFontSize(10);
            doc.text(`Category: ${categoryText}`, 14, startY);
            startY += 6;
        }

        startY += 6;

        // TABLE DATA
        const tableBody = [];
        let totalRemainingQty = 0;
        let totalStockValue = 0;
        let counter = 1;

        stockResponse.data.forEach(function(item) {
            totalRemainingQty += parseFloat(item.remaining_qty);
            totalStockValue += parseFloat(item.stock_value);

            tableBody.push([
                counter++,
                item.item_name,
                parseFloat(item.remaining_qty).toFixed(2),
                parseFloat(item.purchase_price).toFixed(2),
                parseFloat(item.stock_value).toFixed(2)
            ]);
        });

        // Totals row
        tableBody.push([
            '',
            'TOTAL',
            totalRemainingQty.toFixed(2),
            '',
            totalStockValue.toFixed(2)
        ]);

        doc.autoTable({
            startY: startY,
            head: [[
                '#',
                'Item Name',
                'Remaining Qty',
                'Purchase Price',
                'Valuation'
            ]],
            body: tableBody,
            theme: 'grid',
            styles: {
                fontSize: 9,
                cellPadding: 3
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: 'bold'
            },
            columnStyles: {
                2: { halign: 'right' },
                3: { halign: 'right' },
                4: { halign: 'right' }
            }
        });

        doc.save("stock_report.pdf");
    });

    $(document).on("click", '#generate_excel', function() {
        tableId.tableExport({
            formats: ["xlsx"],
            xlsx: {
                onCellFormat: function (cell, e) {
                    if (typeof e.value === 'string') {
                        // Remove commas and convert to number
                        var numValue = parseFloat(e.value.replace(/,/g, ''));
                        if (!isNaN(numValue)) {
                            return numValue;
                        }
                    }
                    return e.value;
                }
            }
        });
    });
    
});//main function
