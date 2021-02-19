jQuery(document).ready(function ($) {
    'use strict';

    check_conditions();
    $("input").change(function (e) {
        check_conditions();
    });

    $("select").change(function (e) {
        check_conditions();
    });

    $("textarea").change(function (e) {
        check_conditions();
    });


    /*conditional fields*/
    function check_conditions() {
        var value;
        var showIfConditionMet = true;

        $(".condition-check").each(function (e) {
            var question = 'burst_' + $(this).data("condition-question");
            var condition_type = 'AND';

            if (question == undefined) return;

            var condition_answer = $(this).data("condition-answer");

            //remove required attribute of child, and set a class.
            var input = $(this).find('input[type=checkbox]');
            if (!input.length) {
                input = $(this).find('input');
            }
            if (!input.length) {
                input = $(this).find('textarea');
            }
            if (!input.length) {
                input = $(this).find('select');
            }

            if (input.length && input[0].hasAttribute('required')) {
                input.addClass('is-required');
            }

            //cast into string
            condition_answer += "";

            if (condition_answer.indexOf('NOT ') !== -1) {
                condition_answer = condition_answer.replace('NOT ', '');
                showIfConditionMet = false;
            } else {
                showIfConditionMet = true;
            }
            var condition_answers = [];
            if (condition_answer.indexOf(' OR ') !== -1) {
                condition_answers = condition_answer.split(' OR ');
                condition_type = 'OR';
            } else {
                condition_answers = [condition_answer];
            }

            var container = $(this);
            var conditionMet = false;
            condition_answers.forEach(function (condition_answer) {
                value = get_input_value(question);

                if ($('select[name=' + question + ']').length) {
                    value = Array($('select[name=' + question + ']').val());
                }

                if ($("input[name='" + question + "[" + condition_answer + "]" + "']").length){
                    if ($("input[name='" + question + "[" + condition_answer + "]" + "']").is(':checked')) {
                        conditionMet = true;
                        value = [];
                    } else {
                        conditionMet = false;
                        value = [];
                    }
                }

                if (showIfConditionMet) {

                    //check if the index of the value is the condition, or, if the value is the condition
                    if (conditionMet || value.indexOf(condition_answer) != -1 || (value == condition_answer)) {

                        container.removeClass("hidden");
                        //remove required attribute of child, and set a class.
                        if (input.hasClass('is-required')) input.prop('required', true);
                        //prevent further checks if it's an or statement
                        if (condition_type === 'OR') conditionMet = true;

                    } else {
                        container.addClass("hidden");
                        if (input.hasClass('is-required')) input.prop('required', false);
                        //prevent further checks if it's an or statement
                        if (condition_type === 'OR') return;
                    }
                } else {

                    if (conditionMet || value.indexOf(condition_answer) != -1 || (value == condition_answer)) {
                        container.addClass("hidden");
                        if (input.hasClass('is-required')) input.prop('required', false);

                    } else {
                        container.removeClass("hidden");
                        if (input.hasClass('is-required')) input.prop('required', true);
                    }
                }
            });

        });
    }


    /**
        get checkbox values, array proof.
    */

    function get_input_value(fieldName) {

        if ($('input[name=' + fieldName + ']').attr('type') == 'text') {
            return $('input[name^=' + fieldName + ']').val();
        } else {
            var checked_boxes = [];
            $('input[name=' + fieldName + ']:checked').each(function () {
                checked_boxes[checked_boxes.length] = $(this).val();
            });
            return checked_boxes;
        }
    }

    //select2 dropdown
    if ($('.burst-select2-page-field').length) {
        burstInitSelect2()
    }

    function burstInitSelect2() {
        // multiple select with AJAX search
        var fieldName = $('.burst-select2-page-field').attr("name");
        var queryName = fieldName + '_query_settings';
        console.log(queryName);
        $('.burst-select2-page-field').select2({
            ajax: {
                    url: ajaxurl, // AJAX URL is predefined in WordPress admin
                    dataType: 'json',
                    delay: 250, // delay in ms while typing when to perform a AJAX search
                    data: function (params) {
                        return {
                            q: params.term, // search query
                            query_settings: window[queryName],
                            action: 'burst_get_posts' // AJAX action for admin-ajax.php
                        };
                    },
                    processResults: function( data ) {
                    var options = [];
                    if ( data ) {
     
                        // data is the array of arrays, and each of them contains ID and the Label of the option
                        $.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
                            options.push( { id: text[0], text: text[1]  } );
                        });
                        console.log(options);
     
                    }
                    return {
                        results: options
                    };
                },
                cache: true
            },
            minimumInputLength: 2, // the minimum of symbols to input before perform a search
            debug: true, //@todo remove debug
            width:'100%',
        });
    }


    /**
     * Ajax loading of tables
     */

    window.burstLoadAjaxTables = function() {
        $('.item-content').each(function () {
            if ($(this).closest('.burst-item').hasClass('burst-load-ajax')) {
                burstLoadData($(this), 1, 0);
            }
        });
    };

    var lastSelectedPage;
    window.burstLoadAjaxTables();
    function burstInitSingleDataTable(container) {
        var table = container.find('.burst-table');
        var win = $(window);
        var pageLength = burstDefaultRowCount;
        var pagingType = burstDefaultPagingType;

        var columnVisible = true;
        if (win.width() < burstScreensizeHideColumn) {
            columnVisible = false;
        }
        var columnTwoDef = '{ "visible": '+columnVisible+',  "targets": [ 2 ] }';
        if (win.width() < burstScreensizeLowerMobile) {
            pageLength = burstMobileRowCount;
            pagingType = burstMobilePagingType;
        }
        table.DataTable( {
            "dom": 'frt<"table-footer"p><"clear">B',
            "pageLength": pageLength,
            "pagingType": pagingType,
            "stateSave": true,
            "columns": [
                { "width": "15%" },
                { "width": "5%" },
                { "width": "12%" },
                { "width": "" },
                { "width": "15%" },
            ],
            "columnDefs": [
                { "visible": false,  "targets": [ 3 ] },
                { "visible": columnVisible,  "targets": [ 2 ] },
                { "iDataSort": 3, "aTargets": [ 2] },
                columnTwoDef,
                { "targets": [1,2,3,4], "searchable": false } //search only on first column
            ],
            buttons: [
                //{extend: 'csv', text: 'Download CSV'}
            ],
            conditionalPaging: true,
            "language": {
                "paginate": {
                    "previous": burst.localize['previous'],
                    "next": burst.localize['next'],
                },
                searchPlaceholder: burst.localize['search'],
                "search": "",
                "emptyTable": burst.localize['no-searches']
            },
            "order": [[2, "desc"]],
        });

        container.find('.burst-table').on( 'page.dt', function () {
            var table = $(this).closest('table').DataTable();
            var info = table.page.info();
            lastSelectedPage = info.page;
        } );
    }


    function localize_html(str) {
        var strings = burst.localize;
        for (var k in strings) {
            if (strings.hasOwnProperty(k)) {
                if ( k === str ) return strings[k];
            }
        }
        return str;
    }


    function burstLoadData(container, page, received){
        var type = container.closest('.burst-item').data('table_type');
        if(page===1) container.html(burst.skeleton);
        var unixStart = localStorage.getItem('burst_range_start');
        var unixEnd = localStorage.getItem('burst_range_end');
        if (unixStart === null || unixEnd === null ) {
            unixStart = moment().subtract(1, 'week').unix();
            unixEnd = moment().unix();
            localStorage.setItem('burst_range_start', unixStart);
            localStorage.setItem('burst_range_end', unixEnd);
        }
        unixStart = parseInt(unixStart);
        unixEnd = parseInt(unixEnd);
        $.ajax({
            type: "GET",
            url: burst.ajaxurl,
            dataType: 'json',
            data: ({
                action : 'burst_get_datatable',
                start  : unixStart,
                end    : unixEnd,
                page   : page,
                type   : type,
                token  : burst.token
            }),
            success: function (response) {
                //this only on first page of table
                if (page===1){
                    container.html(response.html);
                    if (type==='all') {
                        burstInitSingleDataTable(container);
                        burstInitDeleteCapability();
                    }
                } else {
                    var table = container.find('table').DataTable();
                    var rowCount = response.html.length;
                    for (var key in response.html) {
                        if (response.html.hasOwnProperty(key)) {
                            var row = $(response.html[key]);
                            //only redraw on last row
                            if (parseInt(key) >= (rowCount-1) ) {
                                table.row.add(row).draw();
                                table.page( lastSelectedPage ).draw( false )
                            } else {
                                table.row.add(row);
                            }
                        }
                    }
                }

                received += response.batch;
                if (response.total_rows > received) {
                    page++;
                    burstLoadData(container, page , received);
                } else {
                    page = 1;
                }

            }
        });
    }

    /**
     * Start and stop experiment from the dashboard statistics overview
     */

    $(document).on('click', '.burst-statistics-action', function(){

        var type = $(this).data('experiment_action');
        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        burstDisableStartStopBtns();

        $.ajax({
            type: "POST",
            url: burst.ajaxurl,
            dataType: 'json',
            data: ({
                action: 'burst_experiment_action',
                type: type,
                experiment_id: experiment_id,
                token: burst.token
            }),
            success: function (response) {
                if (response.success) {
                    burstEnableStartStopBtns();
                    $('.burst-experiment-start').hide();
                    $('.burst-experiment-stop').hide();
                    if (type==='start') {
                        $('.burst-experiment-stop').show();
                    } else {
                        $('.burst-experiment-start').show();
                    }

                }

            }
        });
    });

    function burstEnableStartStopBtns(){
        $('.burst-experiment-start button').removeAttr('disabled');
        $('.burst-experiment-stop button').removeAttr('disabled');
    }

    function burstDisableStartStopBtns(){
        $('.burst-experiment-start button').attr('disabled', true);
        $('.burst-experiment-stop button').attr('disabled', true);
    }

    function burstInitChartJS() {
        burstDisableStartStopBtns();

        var XscaleLabelDisplay = false;
        var YscaleLabelDisplay = true;
        var titleDisplay = false;
        var legend = true;
        var config = {
            type: 'line',
            data: {
                labels: ['...', '...', '...', '...', '...', '...', '...'],
                datasets: [{
                    label: '...',
                    backgroundColor: 'rgb(255, 99, 132)',
                    borderColor: 'rgb(255, 99, 132)',
                    data: [
                        0, 0, 0, 0, 0, 0, 0,
                    ],
                    fill: false,
                }

                ]
            },
            options: {
                legend:{
                    display:legend,
                },
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: titleDisplay,
                    text: 'Select an experiment'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: XscaleLabelDisplay,
                        scaleLabel: {
                            display: true,
                            labelString: 'Date'
                        }
                    }],
                    yAxes: [{
                        display: YscaleLabelDisplay,
                        scaleLabel: {
                            display: true,
                            labelString: 'Count'
                        },
                        ticks: {
                            beginAtZero: true,
                            min: 0,
                            max: 1,
                            stepSize: 5
                        }
                    }]
                }
            }
        };

        var ctx = document.getElementsByClassName('burst-chartjs-stats');
        window.conversionGraph = new Chart(ctx, config);
        var date_start = localStorage.getItem('burst_range_start');
        var date_end = localStorage.getItem('burst_range_end');

        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        if (experiment_id>0) {
            $.ajax({
                type: "get",
                dataType: "json",
                url: burst.ajaxurl,
                data: {
                    action: "burst_get_experiment_statistics",
                    experiment_id: experiment_id,
                    date_start: date_start,
                    date_end: date_end,
                },
                success: function (response) {
                    if (response.success == true) {
                        var i = 0;
                        response.data.datasets.forEach(function (dataset) {
                            if (config.data.datasets.hasOwnProperty(i)) {
                                config.data.datasets[i] = dataset;
                            } else {
                                var newDataset = dataset;
                                config.data.datasets.push(newDataset);
                            }

                            i++;
                        });
                        config.data.labels = response.data.labels;
                        config.options.title.text = response.title;
                        config.options.scales.yAxes[0].ticks.max = parseInt(response.data.max);
                        window.conversionGraph.update();
                        burstEnableStartStopBtns();
                    } else {
                        alert("Your experiment data could not be loaded")
                    }
                }
            })
        }
    }


    var strToday= burstLocalizeString('Today');
    var strYesterday = burstLocalizeString('Yesterday');
    var strLast7= burstLocalizeString('Last 7 days');
    var strLast30= burstLocalizeString('Last 30 days');
    var strThisMonth= burstLocalizeString('This Month');
    var strLastMonth= burstLocalizeString('Last Month');

    var unixStart = localStorage.getItem('burst_range_start');
    var unixEnd = localStorage.getItem('burst_range_end');

    if (unixStart === null || unixEnd === null ) {
        unixStart = moment().endOf('day').subtract(1, 'week').unix();
        unixEnd = moment().endOf('day').unix();
        localStorage.setItem('burst_range_start', unixStart);
        localStorage.setItem('burst_range_end', unixEnd);
    }

    unixStart = parseInt(unixStart);
    unixEnd = parseInt(unixEnd);
    burstUpdateDate(moment.unix(unixStart), moment.unix(unixEnd));
    burstInitChartJS();

    function burstUpdateDate(start, end) {
        $('.burst-date-container span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        localStorage.setItem('burst_range_start', start.add( moment().utcOffset(), 'm' ).unix());
        localStorage.setItem('burst_range_end', end.add( moment().utcOffset(), 'm' ).unix());
    }
    var todayStart = moment().endOf('day').subtract(1, 'days').add(1, 'minutes');
    var todayEnd = moment().endOf('day');
    var yesterdayStart = moment().endOf('day').subtract(2, 'days').add(1, 'minutes');

    var yesterdayEnd = moment().endOf('day').subtract(1, 'days');
    var lastWeekStart = moment().endOf('day').subtract(8, 'days').add(1, 'minutes');
    var lastWeekEnd = moment().endOf('day').subtract(1, 'days');
    $('.burst-date-container.burst-date-range').daterangepicker(
        {
            ranges: {
                strToday: [todayStart, todayEnd],
                strYesterday : [yesterdayStart, yesterdayEnd],
                strLast7: [lastWeekStart, lastWeekEnd],
                strLast30: [moment().subtract(31, 'days'), yesterdayEnd],
                strThisMonth: [moment().startOf('month'), moment().endOf('month')],
                strLastMonth: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            "locale": {
                "format": burstLocalizeString("date_format","burst"),
                "separator": " - ",
                "applyLabel": burstLocalizeString("Apply","burst"),
                "cancelLabel": burstLocalizeString("Cancel","burst"),
                "fromLabel": burstLocalizeString("From","burst"),
                "toLabel": burstLocalizeString("To","burst"),
                "customRangeLabel": burstLocalizeString("Custom","burst"),
                "weekLabel": burstLocalizeString("W","burst"),
                "daysOfWeek": [
                    burstLocalizeString("Mo","burst"),
                    burstLocalizeString("Tu","burst"),
                    burstLocalizeString("We","burst"),
                    burstLocalizeString("Th","burst"),
                    burstLocalizeString("Fr","burst"),
                    burstLocalizeString("Sa","burst"),
                    burstLocalizeString("Su","burst"),
                ],
                "monthNames": [
                    burstLocalizeString("January"),
                    burstLocalizeString("February"),
                    burstLocalizeString("March"),
                    burstLocalizeString("April"),
                    burstLocalizeString("May"),
                    burstLocalizeString("June"),
                    burstLocalizeString("July"),
                    burstLocalizeString("August"),
                    burstLocalizeString("September"),
                    burstLocalizeString("October"),
                    burstLocalizeString("November"),
                    burstLocalizeString("December")
                ],
                "firstDay": 1
            },
            "alwaysShowCalendars": true,
                startDate: moment.unix(unixStart),
                endDate: moment.unix(unixEnd),
                "opens": "left",
        }, function (start, end, label) {
                burstUpdateDate(start, end);
                burstInitChartJS();
    });

    $(document).on('change', 'select[name=burst_selected_experiment_id]', function(){
        burstInitChartJS();
    });

    function burstLocalizeString(str) {
        var strings = burst.strings;
        for (var k in strings) {
            if (strings.hasOwnProperty(k)) {
                str = str.replaceAll(k, strings[k]);
            }
        }
        return str;
    }

    burstLoadGridBlocks();
    function burstLoadGridBlocks(){
        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        var date_start = localStorage.getItem('burst_range_start');
        var date_end = localStorage.getItem('burst_range_end');

        $('.burst-load-ajax').each(function(){
            var gridContainer = $(this);
            var type = gridContainer.data('table_type');
            $.ajax({
                type: "get",
                dataType: "json",
                url: burst.ajaxurl,
                data: {
                    action: "burst_load_grid_block",
                    experiment_id: experiment_id,
                    date_start: date_start,
                    date_end: date_end,
                    type:type,
                },
                success: function (response) {
                    console.log(response);
                    if (response.success) {
                        gridContainer.find('.item-content').html(response.html);
                    }
                }
            })
        });
    }



});

