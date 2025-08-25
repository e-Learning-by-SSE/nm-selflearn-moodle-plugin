define(['jquery', 'core/ajax'], function($, Ajax) {
    return {
        init: function() {
            const toggle = $('#toggle');
            const searchInput = $('input[name="search_input"]');

            /**
             * REST API query for courses that match selection.
             */
            function updateCourses() {
                let searchQuery = searchInput.val();
                let toggleState = toggle.prop('checked') ? 0 : 1;

                var request = Ajax.call([{
                    methodname: 'selflearn_search_items',
                    args: { search: searchQuery, fromAllAuthors: toggleState}
                }]);
                request[0].then(function(data) {
                    var course_select = $('#id_course_selection');
                    course_select.empty();

                    var options = [];
                    if (data && data.length >0) {
                        data.forEach(function(course) {
                            var option = {
                                value: course.id,
                                text: course.name
                            };
                            course_select.append($('<option>', option));
                            options.push(option);
                        });
                        course_select.value = data[0].id;
                    }

                    $('[name="course_select"]').val(JSON.stringify(options));
                }).fail(function(error) {
                    window.console.log('AJAX request failed', error);
                });
            }

            // Attach event listeners
            toggle.on('change', updateCourses);
            searchInput.on('input', updateCourses);
        }
    };
});
