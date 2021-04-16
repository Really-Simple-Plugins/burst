jQuery(document).ready(function ($) {
    'use strict';

    $(document).on('click', '.burst-dismiss-notice', function(){
        var notice_id = $(this).data('notice_id');
        var btn = $(this);
        btn.attr('disabled', 'disabled');
        $.ajax({
            type: "POST",
            url: burst.ajaxurl,
            dataType: 'json',
            data: ({
                action: 'burst_dismiss_notice',
                id: notice_id,
            }),
            success: function (response) {
                btn.removeAttr('disabled');
                if (response.success) {
                    btn.closest('.burst-notice').remove();
                }
            }
        });
    });


    /**
     * Start and stop experiment from the dashboard statistics overview
     */

    $(document).on('click', '.burst-statistics-action', function () {

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
                    if (type === 'start') {
                        $('.burst-experiment-stop').show();
                    } else {
                        $('.burst-experiment-start').show();
                    }

                }

            }
        });
    });

    function burstEnableStartStopBtns() {
        $('.burst-experiment-start button').removeAttr('disabled');
        $('.burst-experiment-stop button').removeAttr('disabled');
        $('.burst_selected_experiment_id_wrapper select').removeAttr('disabled');
        
    }

    function burstDisableStartStopBtns() {
        $('.burst-experiment-start button').attr('disabled', true);
        $('.burst-experiment-stop button').attr('disabled', true);
        $('.burst_selected_experiment_id_wrapper select').attr('disabled', true);
    }

    function burstInitChartJS(date_start, date_end) {
        var useExperimentDate = typeof date_start === 'undefined';
        date_start = typeof date_start !== 'undefined' ? date_start : parseInt($('input[name=burst_experiment_start]').val());
        date_end = typeof date_end !== 'undefined' ? date_end : parseInt($('input[name=burst_experiment_end]').val());
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
                    backgroundColor: 'rgb(41, 182, 246)',
                    borderColor: 'rgb(41, 182, 246)',
                    data: [
                        0, 0, 0, 0, 0, 0, 0,
                    ],
                    fill: false,
                },
                {
                    label: '...',
                    backgroundColor: 'rgb(244, 191, 62)',
                    borderColor: 'rgb(244, 191, 62)',
                    data: [
                        0, 0, 0, 0, 0, 0, 0,
                    ],
                    fill: false,
                }]
            },
            options: {
                legend: {
                    display: legend,
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
                            max: 10,
                            stepSize: 5
                        }
                    }]
                }
            }
        };

        var ctx = document.getElementsByClassName('burst-chartjs-stats');
        window.conversionGraph = new Chart(ctx, config);


        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        if (experiment_id > 0) {
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
                        if (useExperimentDate) {
                            $('input[name=burst_experiment_start]').val(response.data.date_start);
                            $('input[name=burst_experiment_end]').val(response.data.date_end);
                            burstUpdateDate(moment.unix(response.data.date_start), moment.unix(response.data.date_end));
                        }

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
                        config.options.scales.yAxes[0].ticks.max = 20;//parseInt(response.data.max);
                        window.conversionGraph.update();
                        burstEnableStartStopBtns();
                    } else {
                        alert("Your experiment data could not be loaded")
                    }
                }
            })
        }
    }

    initDatePicker();
    function initDatePicker() {
        var strToday = burstLocalizeString('Today');
        var strYesterday = burstLocalizeString('Yesterday');
        var strLast7 = burstLocalizeString('Last 7 days');
        var strLast30 = burstLocalizeString('Last 30 days');
        var strThisMonth = burstLocalizeString('This Month');
        var strLastMonth = burstLocalizeString('Last Month');

        var ranges = {}
        ranges[strToday] = [todayStart, todayEnd];
        ranges[strYesterday] = [yesterdayStart, yesterdayEnd];
        ranges[strLast7] = [lastWeekStart, lastWeekEnd];
        ranges[strLast30] = [moment().subtract(31, 'days'), yesterdayEnd];
        ranges[strThisMonth] = [moment().startOf('month'), moment().endOf('month')];
        ranges[strLastMonth] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];

        var unixStart = parseInt($('input[name=burst_experiment_start]').val());
        var unixEnd = parseInt($('input[name=burst_experiment_end]').val());

        if (unixStart === null || unixEnd === null) {
            unixStart = moment().endOf('day').subtract(1, 'week').unix();
            unixEnd = moment().endOf('day').unix();
        }

        unixStart = parseInt(unixStart);
        unixEnd = parseInt(unixEnd);
        //burstUpdateDate(moment.unix(unixStart), moment.unix(unixEnd));
        burstInitChartJS();

        var todayStart = moment().endOf('day').subtract(1, 'days').add(1, 'minutes');
        var todayEnd = moment().endOf('day');
        var yesterdayStart = moment().endOf('day').subtract(2, 'days').add(1, 'minutes');

        var yesterdayEnd = moment().endOf('day').subtract(1, 'days');
        var lastWeekStart = moment().endOf('day').subtract(8, 'days').add(1, 'minutes');
        var lastWeekEnd = moment().endOf('day').subtract(1, 'days');
        $('.burst-date-container.burst-date-range').daterangepicker(
            {
                ranges: ranges,
                "locale": {
                    "format": burstLocalizeString("date_format", "burst"),
                    "separator": " - ",
                    "applyLabel": burstLocalizeString("Apply", "burst"),
                    "cancelLabel": burstLocalizeString("Cancel", "burst"),
                    "fromLabel": burstLocalizeString("From", "burst"),
                    "toLabel": burstLocalizeString("To", "burst"),
                    "customRangeLabel": burstLocalizeString("Custom", "burst"),
                    "weekLabel": burstLocalizeString("W", "burst"),
                    "daysOfWeek": [
                        burstLocalizeString("Mo", "burst"),
                        burstLocalizeString("Tu", "burst"),
                        burstLocalizeString("We", "burst"),
                        burstLocalizeString("Th", "burst"),
                        burstLocalizeString("Fr", "burst"),
                        burstLocalizeString("Sa", "burst"),
                        burstLocalizeString("Su", "burst"),
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
                burstInitChartJS(start.unix(), end.unix());
            });
    }

    $(document).on('change', 'select[name=burst_selected_experiment_id]', function(){
        burstInitChartJS();
        burstLoadGridBlocks();
        burstLoadStatusInfo();
    });

    burstLoadGridBlocks();
    function burstLoadGridBlocks(){
        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        // var date_start = localStorage.getItem('burst_range_start');
        // var date_end = localStorage.getItem('burst_range_end');
        var date_start = parseInt($('input[name=burst_experiment_start]').val());
        var date_end = parseInt($('input[name=burst_experiment_end]').val());

        $('.burst-load-ajax').each(function(){
            var gridContainer = $(this);
            // if there is no skeleton add a skeleton
            if (gridContainer.find('.burst-skeleton').length == 0) {
                 gridContainer.find('.burst-grid-content').fadeOut(200, function() {
                        $(this).html('<div class="burst-skeleton"></div>').fadeIn(300);
                });
            }
            
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
                    if (response.success) {
                        gridContainer.find('.burst-grid-content').fadeOut(300, function() {
                            $(this).html(response.html).fadeIn(200);
                        })
                    }
                }
            })
        });
    }

    function burstUpdateDate(start, end) {
        $('.burst-date-container span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    }

    burstLoadStatusInfo();
    function burstLoadStatusInfo(){
        var experiment_id = $('select[name=burst_selected_experiment_id]').val();

        // animate status
        if ( $('.burst-experiment-status').find('.loading').length == 0 ) {
            $('.burst-experiment-status .burst-bullet').removeClass().addClass('burst-bullet grey loading');
            $('.burst-experiment-status .burst-experiment-status__text').fadeOut(300, function() {
                $(this).html('Loading...').fadeIn(200);
            });
            $('.burst-experiment-stop').fadeOut();
            $('.burst-experiment-completed').fadeOut(200);

        };

        $.ajax({
            type: "get",
            dataType: "json",
            url: burst.ajaxurl,
            data: {
                action: "burst_load_status_info",
                experiment_id: experiment_id,
            },
            success: function (response) {
                if (response.success) {
                    console.log(response);
                    $('.burst-experiment-status .burst-bullet').removeClass().addClass('burst-bullet ' + response.data.status.class);
                    $('.burst-experiment-status .burst-experiment-status__text').fadeOut(300, function() {
                        $(this).html(response.data.status.title).fadeIn(200);
                    });
                    $('.burst-experiment-stop').hide();
                    $('.burst-experiment-completed').hide();
                    if (response.data.status.title === 'Active') {
                        $('.burst-experiment-stop').show();
                    } else if(response.data.status.title === 'Completed'){
                        $('.burst-experiment-completed-text').html(response.data.date_end_text)
                        $('.burst-experiment-completed').show();
                    }
                    // gridContainer.find('.burst-skeleton').fadeOut(500, function() {
                    //     gridContainer.find('.burst-grid-content').hide().html(response.html).fadeIn(1000);
                    // })
                }
            }
        })
    }


});

