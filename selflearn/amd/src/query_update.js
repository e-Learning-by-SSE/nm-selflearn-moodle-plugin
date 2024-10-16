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

                    data.forEach(function(course) {
                        window.console.log('course: ' + JSON.stringify(course));
                        course_select.append($('<option>', {
                            value: course.id,
                            text: course.name
                        }));
                    });
                }).fail(function(error) {
                    window.console.log('AJAX request failed', error);
                });
            });
        }
    };
});