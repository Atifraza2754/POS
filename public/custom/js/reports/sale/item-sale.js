$(function() {
    "use strict";

    let originalButtonText;
    let itemSaleResponse = null;

    const tableId = $('#itemSaleReport');

    /**
     * Language
     * */
    const _lang = {
                total : "Total",
                noRecordsFound : "No Records Found!!",
            };

    $("#reportForm").on("submit", function(e) {
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
    function afterSeccessOfAjaxRequest(formObject, response){
        formAdjustIfSaveOperation(response);
        // Keep last response for PDF export
        itemSaleResponse = response;
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

    let totalQuantity = 0;
    let totalProfit = 0;

    // Loop through date → items
    $.each(response.data, function(date, items) {
        $.each(items, function(itemId, row) {

            const quantity = parseFloat(row.qty);
            const profit = parseFloat(row.profit);

            totalQuantity += quantity;
            totalProfit += profit;
            const userName = row.user_name ?? "N/A";

            tr += `
                <tr>
                    <td>${id++}</td>
                    <td>${date}</td>
                     <td class="text-start">${userName}</td>
                    <td>${row.category_name}</td>
                    <td>${row.item_name}</td>
                    <td class="text-start" data-tableexport-celltype="number">
                        ${_formatNumber(quantity)}
                    </td>
                    <td class="text-start" data-tableexport-celltype="number">
                        ${_formatNumber(profit)}
                    </td>
                </tr>
            `;
        });
    });

    // Totals Row
    tr += `
        <tr class='fw-bold'>
            <td colspan="5" class='text-end tfoot-first-td'>${_lang.total}</td>
            <td class="text-start">${_formatNumber(totalQuantity)}</td>
            <td class="text-start">${_formatNumber(totalProfit)}</td>
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
        if (!itemSaleResponse || !itemSaleResponse.data) {
            alert('No data to export');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');

        let startY = 15;
        const pageWidth = doc.internal.pageSize.getWidth();

        // Company name (centered)
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Shahid Traders", pageWidth / 2, startY, { align: "center" });

        startY += 8;

        // Report title
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        doc.text("Item Sale Report", pageWidth / 2, startY, { align: "center" });

        startY += 10;

        // User & Period
        const userName = (itemSaleResponse.user && itemSaleResponse.user.username) ? itemSaleResponse.user.username : 'N/A';
        const fromDate = itemSaleResponse.from_date || ($('#reportForm').find('input[name="from_date"]').val() || '-');
        const toDate = itemSaleResponse.to_date || ($('#reportForm').find('input[name="to_date"]').val() || '-');

        doc.setFontSize(10);
        doc.text(`User: ${userName}`, 14, startY);
        startY += 6;
        doc.text(`Period: ${fromDate} - ${toDate}`, 14, startY);

        startY += 10;

        // Build table body from grouped data (date -> items)
        const tableBody = [];
        let totalQty = 0;
        let totalProfit = 0;

        $.each(itemSaleResponse.data, function(date, items) {
            $.each(items, function(itemId, row) {
                tableBody.push([
                    date,
                    row.user_name || 'N/A',
                    row.category_name || 'N/A',
                    row.item_name || 'N/A',
                    (row.qty || 0).toFixed(2),
                    (row.profit || 0).toFixed(2),
                ]);

                totalQty += parseFloat(row.qty || 0);
                totalProfit += parseFloat(row.profit || 0);
            });
        });

        // Totals row
        tableBody.push(['', '', '', 'TOTAL', totalQty.toFixed(2), totalProfit.toFixed(2)]);

        doc.autoTable({
            startY: startY,
            head: [[ 'Date', 'User', 'Category', 'Item', 'Quantity', 'Profit' ]],
            body: tableBody,
            theme: 'grid',
            styles: { fontSize: 9, cellPadding: 3 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
            columnStyles: { 4: { halign: 'right' }, 5: { halign: 'right' } }
        });

        doc.save("item_sale_report.pdf");
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
