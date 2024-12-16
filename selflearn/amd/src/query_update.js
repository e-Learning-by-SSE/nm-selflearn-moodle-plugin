define(['jquery', 'core/ajax'] , function ($, Ajax) {
    return {
        queryCourses: function() {
            $('#id_search_input').on('input', function() {
                var userInput  = $(this);
                var searchText = "" + userInput.val();
                window.console.log('Input: ' + searchText + ", URL: "+userInput.data('data-ajax-url'));
                var request = Ajax.call([{
                    methodname: 'selflearn_search_items',
                    args: { search: searchText}
                }]);
                request[0].then(function(data) {
                    window.console.log('Data: ' + JSON.stringify(data));
                    var course_select = $('#id_course_selection');
                    course_select.empty();

                    var options = [];
                    data.forEach(function(course) {
                        window.console.log('course: ' + JSON.stringify(course));
                        var option = {
                            value: course.id,
                            text: course.name
                        };
                        course_select.append($('<option>', option));
                        options.push(option);
                    });

                    course_select.value = data[0].id;
                    course_select._qf.element.options = options;
                    $('[name="course_select"]').val(JSON.stringify(options));

                    // // Add form data update handler
                    // course_select.on('change', function() {
                    //     $(this).closest('form').data('course_selection', $(this).val());
                    // });
                    // // Trigger initial update
                    // course_select.trigger('change');
                    // course_select.prop('required', true);
                    // course_select.required = true;
                }).fail(function(error) {
                    window.console.log('AJAX request failed', error);
                });
            });
        }
    };
});