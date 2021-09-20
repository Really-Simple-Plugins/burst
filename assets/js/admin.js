jQuery(document).ready(function ($) {
    'use strict';

    check_conditions();

    $(document).on('change', 'input', function (e) {
        check_conditions();
    });

    $(document).on('change', 'select', function (e) {
        check_conditions();
    });

    $(document).on('change', 'textarea', function (e) {
        check_conditions();
    });

    $('.duplicate_burst_variant_id').change(function(){
        console.log('change');
        // switches the disabled attribute
        console.log($('.duplicate_burst_variant_id:checked').val());
        var selectField = $('select[name=burst_variant_id]');
        if (selectField.attr('disabled')) {
            $(selectField).removeAttr('disabled');
        } else {
            $(selectField).attr('disabled', 'disabled');
        }

    });

    /*conditional fields*/
    function check_conditions() {
        var value;
        var showIfConditionMet = true;

        $(".condition-check-1").each(function (e) {
            var i;
            for (i = 1; i < 4; i++) {
                var question = 'burst_' + $(this).data("condition-question-" + i);
                var condition_type = 'AND';

                if (question == 'burst_undefined') return;

                var condition_answer = $(this).data("condition-answer-" + i);

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
                    if ($("input[name='" + question + "[" + condition_answer + "]" + "']").length) {

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
                            container.removeClass("burst-hidden");
                            //remove required attribute of child, and set a class.
                            if (input.hasClass('is-required')) input.prop('required', true);
                            //prevent further checks if it's an or/and statement
                            conditionMet = true;
                        } else {
                            container.addClass("burst-hidden");
                            if (input.hasClass('is-required')) input.prop('required', false);
                        }
                    } else {
                        if (conditionMet || value.indexOf(condition_answer) != -1 || (value == condition_answer)) {
                            container.addClass("burst-hidden");
                            if (input.hasClass('is-required')) input.prop('required', false);
                        } else {
                            container.removeClass("burst-hidden");
                            if (input.hasClass('is-required')) input.prop('required', true);
                            conditionMet = true;
                        }
                    }
                });
                if (!conditionMet) {
                    break;
                }
            }
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

    // Select/focus the searchbox when clicking on a select2 field
    $(document).on('select2:open', () => {
        document.querySelector('.select2-search__field').focus();
    });

    $(document).on('click', '.burst-experiment-action', function (e) {
        e.preventDefault();
        var btn = $(this);
        btn.closest('tr').css({'background-color': '#D7263D', 'opacity': '0.80'});
        var experiment_id = btn.data('id');
        var type = btn.data('action');
        $.ajax({
            type: "POST",
            url: burst.ajaxurl,
            dataType: 'json',
            data: ({
                action: 'burst_experiment_action',
                experiment_id: experiment_id,
                type: type
            }),
            success: function (response) {
                if (response.success) {
                    btn.closest('tr').fadeOut(300, function() {
                        this.remove();
                    });
                }
            }
        });
    });
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