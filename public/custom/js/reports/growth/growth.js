$(document).ready(function () {
    function formatNumber(num) {
        return parseFloat(num || 0).toFixed(2);
    }

    var growthResponse = null;

    $('#reportForm').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
                    success: function (res) {
                        if (res && res.status) {
                            var d = res.data || {};
                            growthResponse = res; // store full response for PDF export
                            $('#stock_value').text(formatNumber(d.stock_value));
                            $('#due_on_customer').text(formatNumber(d.due_on_customer));
                            $('#purchase_supplier_balance').text(formatNumber(d.purchase_supplier_balance));
                            $('#cash_in_hand').text(formatNumber(d.cash_in_hand));
                            $('#total_profit').text(formatNumber(d.total_profit));
                        } else {
                            alert(res.message || 'No data found');
                        }
                    },
            error: function (err) {
                console.error(err);
                alert('Server error');
            }
        });
    });

    $(document).on('click', '#generate_excel', function () {

        $('#reportTable').tableExport({

            formats: ["xlsx"],

            xlsx: {

                onCellFormat: function (cell, e) {

                    if (typeof e.value === 'string') {

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


    $(document).on('click', '#generate_pdf', function () {

        if (!growthResponse || !growthResponse.data) {

            alert('No growth data to export');

            return;
        }

        const { jsPDF } = window.jspdf;

        const doc = new jsPDF('p', 'mm', 'a4');

        let startY = 15;

        const pageWidth = doc.internal.pageSize.getWidth();


        // COMPANY NAME (use same as Profit PDF)
        doc.setFontSize(18);
        doc.setFont('helvetica', 'bold');

        doc.text('Shahid Traders', pageWidth / 2, startY, { align: 'center' });

        startY += 8;


        // REPORT TITLE
        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');

        doc.text('Growth Report', pageWidth / 2, startY, { align: 'center' });

        startY += 10;


        // PERIOD
        doc.setFontSize(10);

        const fromDate = $("input[name='from_date']").val();
        const toDate = $("input[name='to_date']").val();

        doc.text(`Period: ${fromDate} - ${toDate}`, 14, startY);

        startY += 10;


        const tableBody = [];

        function parseAmount(val) {
            const v = (val === null || val === undefined) ? 0 : String(val).replace(/,/g, '');
            const n = parseFloat(v);
            return isNaN(n) ? '0.00' : n.toFixed(2);
        }

        const d = growthResponse.data;

        tableBody.push([
            '1',
            'Total Stock value',
            parseAmount(d.stock_value)
        ]);

        tableBody.push([
            '2',
            'Total Due On Customer',
            parseAmount(d.due_on_customer)
        ]);

        tableBody.push([
            '3',
            'Total Purchase Supplier Balance',
            parseAmount(d.purchase_supplier_balance)
        ]);

        tableBody.push([
            '4',
            'Cash In Hand',
            parseAmount(d.cash_in_hand)
        ]);

        tableBody.push([
            '5',
            'Total Profit',
            parseAmount(d.total_profit)
        ]);


        doc.autoTable({

            startY: startY,

            head: [[
                '#',
                'Title',
                'Amount'
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
                2: { halign: 'right' }
            }

        });


        doc.save('growth_report.pdf');

    });

});
