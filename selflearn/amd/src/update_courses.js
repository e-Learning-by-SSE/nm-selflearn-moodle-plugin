define(['jquery', 'core/ajax'], function($, Ajax) {
    return {
        init: function() {
            const toggle = $('#toggle');
            const toggleState = toggle.prop('checked') ? 0 : 1;
            const searchInput = $('input[name="search_input"]');
            var searchText = "" + searchInput.val();

            // const ajaxUrl = toggle.data('ajax-url'); // Get AJAX URL from the toggle element
            const ajaxUrl = '/mod/selflearn/search.php';
            window.console.log('Search: ' + searchText, "Authors: " + toggleState, "URL: " + ajaxUrl);

            /**
             * REST API query for courses that match selection.
             */
            function updateCourses() {
                let searchQuery = searchInput.val();
                let toggleState = toggle.prop('checked') ? 0 : 1;
                window.console.log('Search: ' + searchQuery, "Authors: " + toggleState);

                var request = Ajax.call([{
                    methodname: 'selflearn_search_items',
                    args: { search: searchQuery, fromAllAuthors: toggleState}
                }]);
                request[0].then(function(data) {
                    window.console.log('Query Results: ' + JSON.stringify(data));
                    var course_select = $('#id_course_selection');
                    course_select.empty();

                    var options = [];
                    if (data && data.length >0) {
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
                    }

                    // course_select._qf.element.options = options;
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
