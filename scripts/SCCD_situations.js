
$(function() {
    $('#dynamic_region').keyup(function() {
        $('table.all_sits tr.row_filtered').each(function() {
            var passed = true;
            $(this).children().each(function () {
                var filter_id = $(this).attr('class');
                if (filter_id == 'pfr_locations') {
                    return true;
                }
                else {
                    var filter_value = $("#" + filter_id).val().toUpperCase();
                    passed = (passed && (this.innerHTML.toUpperCase().indexOf(filter_value) >= 0));
                }
            });
            $(this).toggle(passed);
        });
    });
});

$(function() {
    $('#dynamic_node').keyup(function() {
        $('table.all_sits tr.row_filtered').each(function() {
            var passed = true;
            $(this).children().each(function () {
                var filter_id = $(this).attr('class');
                if (filter_id == 'pfr_locations') {
                    return true;
                }
                else {
                    var filter_value = $("#" + filter_id).val().toUpperCase();
                    passed = (passed && (this.innerHTML.toUpperCase().indexOf(filter_value) >= 0));
                }
            });
            $(this).toggle(passed);
        });
    });
});

$(function() {
    $('#dynamic_situation').keyup(function() {
        $('table.all_sits tr.row_filtered').each(function() {
            var passed = true;
            $(this).children().each(function () {
                var filter_id = $(this).attr('class');
                if (filter_id == 'pfr_locations') {
                    return true;
                }
                else {
                    var filter_value = $("#" + filter_id).val().toUpperCase();
                    passed = (passed && (this.innerHTML.toUpperCase().indexOf(filter_value) >= 0));
                }
            });
            $(this).toggle(passed);
        });
    });
});

// свёртывание/развёртывание строк с образцами записей из PFR_LOCATIONS
$(function() {
    $('.tr_rec_toggle').click(function() {
        $('tr.rec_show').toggle(function() {
            $(this).animate({}, 2000);
        });
        $('tr.rec_hide').toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});

