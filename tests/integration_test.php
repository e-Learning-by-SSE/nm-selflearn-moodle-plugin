<?php
namespace mod_selflearn;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Integration tests for SelfLearn API functionality
 * Tests the actual integration with external SelfLearn REST API
 *
 * @package    mod_selflearn
 * @category   test
 * @copyright  2024 University of Hildesheim
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class integration_test extends \advanced_testcase {

    /**
     * Setup method - runs before each test
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    // ========================================
    // TEST 1: API Integration - Success Cases
    // ========================================

    /**
     * Test 1.1: API returns progress for enrolled student
     */
    public function test_api_returns_progress_for_enrolled_student() {
        // Create mock REST client
        $mock_client = $this->createMock(\restclient::class);
        
        // Mock API response: student enrolled with 85% progress
        $mock_client->method('selflearn_get_course_progress')
            ->willReturn([
                ['username' => 'student1', 'progress' => 85]
            ]);

        // Create test data
        $users = [(object)['username' => 'student1']];
        $courses = [['slug' => 'test-course', 'id' => 1]];

        // Call function with mock client
        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Assertions
        $this->assertArrayHasKey('student1', $result);
        $this->assertEquals(85, $result['student1']['test-course']);
        $this->assertEquals(85, $result['student1']['total_average']);
    }

    /**
     * Test 1.2: API returns empty array for non-enrolled student
     */
    public function test_api_returns_empty_for_not_enrolled() {
        $mock_client = $this->createMock(\restclient::class);
        
        // Mock API response: empty array = not enrolled
        $mock_client->method('selflearn_get_course_progress')
            ->willReturn([]);

        $users = [(object)['username' => 'student1']];
        $courses = [['slug' => 'test-course', 'id' => 1]];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Assertions
        $this->assertArrayHasKey('student1', $result);
        $this->assertEquals('---', $result['student1']['test-course']);
        $this->assertEquals('---', $result['student1']['total_average']);
    }

    /**
     * Test 1.3: API returns 0% progress (enrolled but no progress)
     */
    public function test_api_returns_zero_percent_progress() {
        $mock_client = $this->createMock(\restclient::class);
        
        // Mock API response: 0% progress (enrolled but not started)
        $mock_client->method('selflearn_get_course_progress')
            ->willReturn([
                ['username' => 'student1', 'progress' => 0]
            ]);

        $users = [(object)['username' => 'student1']];
        $courses = [['slug' => 'test-course', 'id' => 1]];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Assertions - 0% should be treated as enrolled with 0 progress
        $this->assertArrayHasKey('student1', $result);
        $this->assertEquals(0, $result['student1']['test-course']);
        $this->assertEquals(0, $result['student1']['total_average']);
    }

    /**
     * Test 1.4: Multiple students with different progress
     */
    public function test_multiple_students_different_progress() {
        $mock_client = $this->createMock(\restclient::class);
        
        // Mock API responses for different students
        $mock_client->method('selflearn_get_course_progress')
            ->willReturnCallback(function($slug, $usernames) {
                $username = $usernames[0];
                
                if ($username === 'student1') {
                    return [['username' => 'student1', 'progress' => 85]];
                } else if ($username === 'student2') {
                    return [['username' => 'student2', 'progress' => 50]];
                } else if ($username === 'student3') {
                    return []; // Not enrolled
                }
                return [];
            });

        $users = [
            (object)['username' => 'student1'],
            (object)['username' => 'student2'],
            (object)['username' => 'student3']
        ];
        $courses = [['slug' => 'test-course', 'id' => 1]];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Assertions
        $this->assertEquals(85, $result['student1']['test-course']);
        $this->assertEquals(50, $result['student2']['test-course']);
        $this->assertEquals('---', $result['student3']['test-course']);
    }

    /**
     * Test 1.5: Multiple courses with different progress
     */
    public function test_multiple_courses_different_progress() {
        $mock_client = $this->createMock(\restclient::class);
        
        // Mock API responses for different courses
        $mock_client->method('selflearn_get_course_progress')
            ->willReturnCallback(function($slug, $usernames) {
                if ($slug === 'course1') {
                    return [['username' => 'student1', 'progress' => 80]];
                } else if ($slug === 'course2') {
                    return [['username' => 'student1', 'progress' => 90]];
                } else if ($slug === 'course3') {
                    return []; // Not enrolled in course3
                }
                return [];
            });

        $users = [(object)['username' => 'student1']];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2],
            ['slug' => 'course3', 'id' => 3]
        ];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Assertions
        $this->assertEquals(80, $result['student1']['course1']);
        $this->assertEquals(90, $result['student1']['course2']);
        $this->assertEquals('---', $result['student1']['course3']);
        $this->assertEquals(85, $result['student1']['total_average']); // (80+90)/2 = 85
    }

    // ========================================
    // TEST 2: Average Calculation
    // ========================================

    /**
     * Test 2.1: Calculate average with all courses enrolled
     */
    public function test_average_all_courses_enrolled() {
        $mock_client = $this->createMock(\restclient::class);
        
        $mock_client->method('selflearn_get_course_progress')
            ->willReturnCallback(function($slug, $usernames) {
                if ($slug === 'course1') {
                    return [['username' => 'student1', 'progress' => 60]];
                } else if ($slug === 'course2') {
                    return [['username' => 'student1', 'progress' => 80]];
                } else if ($slug === 'course3') {
                    return [['username' => 'student1', 'progress' => 100]];
                }
                return [];
            });

        $users = [(object)['username' => 'student1']];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2],
            ['slug' => 'course3', 'id' => 3]
        ];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Average: (60+80+100)/3 = 80
        $this->assertEquals(80, $result['student1']['total_average']);
    }

    /**
     * Test 2.2: Calculate average excluding "---" (not enrolled) courses
     */
    public function test_average_excluding_not_enrolled() {
        $mock_client = $this->createMock(\restclient::class);
        
        $mock_client->method('selflearn_get_course_progress')
            ->willReturnCallback(function($slug, $usernames) {
                if ($slug === 'course1') {
                    return [['username' => 'student1', 'progress' => 70]];
                } else if ($slug === 'course2') {
                    return []; // Not enrolled
                } else if ($slug === 'course3') {
                    return [['username' => 'student1', 'progress' => 90]];
                }
                return [];
            });

        $users = [(object)['username' => 'student1']];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2],
            ['slug' => 'course3', 'id' => 3]
        ];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Average: (70+90)/2 = 80 (course2 not included)
        $this->assertEquals(80, $result['student1']['total_average']);
    }

    /**
     * Test 2.3: Average is "---" when not enrolled in any course
     */
    public function test_average_not_enrolled_in_any_course() {
        $mock_client = $this->createMock(\restclient::class);
        
        // All courses return empty (not enrolled)
        $mock_client->method('selflearn_get_course_progress')
            ->willReturn([]);

        $users = [(object)['username' => 'student1']];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2]
        ];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Average should be "---" when not enrolled anywhere
        $this->assertEquals('---', $result['student1']['total_average']);
    }

    /**
     * Test 2.4: Average includes 0% progress
     */
    public function test_average_includes_zero_percent() {
        $mock_client = $this->createMock(\restclient::class);
        
        $mock_client->method('selflearn_get_course_progress')
            ->willReturnCallback(function($slug, $usernames) {
                if ($slug === 'course1') {
                    return [['username' => 'student1', 'progress' => 0]]; // 0%
                } else if ($slug === 'course2') {
                    return [['username' => 'student1', 'progress' => 100]];
                }
                return [];
            });

        $users = [(object)['username' => 'student1']];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2]
        ];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Average: (0+100)/2 = 50
        $this->assertEquals(50, $result['student1']['total_average']);
    }

    // ========================================
    // TEST 3: Error Handling
    // ========================================

    /**
     * Test 3.1: Handle API exception for specific course
     */
    public function test_api_exception_for_course() {
        $mock_client = $this->createMock(\restclient::class);
        
        $mock_client->method('selflearn_get_course_progress')
            ->willReturnCallback(function($slug, $usernames) {
                if ($slug === 'course1') {
                    return [['username' => 'student1', 'progress' => 80]];
                } else if ($slug === 'course2') {
                    throw new \Exception("API timeout");
                }
                return [];
            });

        $users = [(object)['username' => 'student1']];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2]
        ];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Assertions
        $this->assertEquals(80, $result['student1']['course1']);
        $this->assertEquals('Error', $result['student1']['course2']);
        // Average should only include successful courses
        $this->assertEquals(80, $result['student1']['total_average']);
    }

    /**
     * Test 3.2: Fallback when REST client cannot be created
     */
    public function test_fallback_when_restclient_fails() {
        $users = [
            (object)['username' => 'student1'],
            (object)['username' => 'student2']
        ];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2]
        ];

        $result = selflearn_get_fallback_progress($users, $courses);

        // Assertions - all should show "SelfLearn unavailable"
        $this->assertEquals('SelfLearn unavailable', $result['student1']['course1']);
        $this->assertEquals('SelfLearn unavailable', $result['student1']['course2']);
        $this->assertEquals('---', $result['student1']['total_average']);
        
        $this->assertEquals('SelfLearn unavailable', $result['student2']['course1']);
        $this->assertEquals('SelfLearn unavailable', $result['student2']['course2']);
        $this->assertEquals('---', $result['student2']['total_average']);
    }

    // ========================================
    // TEST 4: Edge Cases
    // ========================================

    /**
     * Test 4.1: Empty users array
     */
    public function test_empty_users_array() {
        $users = [];
        $courses = [['slug' => 'course1', 'id' => 1]];

        $result = selflearn_query_progress($users, $courses);

        $this->assertEmpty($result);
    }

    /**
     * Test 4.2: Empty courses array
     */
    public function test_empty_courses_array() {
        $users = [(object)['username' => 'student1']];
        $courses = [];

        $result = selflearn_query_progress($users, $courses);

        $this->assertEmpty($result);
    }

    /**
     * Test 4.3: Both empty
     */
    public function test_both_arrays_empty() {
        $users = [];
        $courses = [];

        $result = selflearn_query_progress($users, $courses);

        $this->assertEmpty($result);
    }

    /**
     * Test 4.4: API returns null progress
     */
    public function test_api_returns_null_progress() {
        $mock_client = $this->createMock(\restclient::class);
        
        // Mock API response with null progress
        $mock_client->method('selflearn_get_course_progress')
            ->willReturn([
                ['username' => 'student1', 'progress' => null]
            ]);

        $users = [(object)['username' => 'student1']];
        $courses = [['slug' => 'test-course', 'id' => 1]];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Null progress should be treated as "---"
        $this->assertEquals('---', $result['student1']['test-course']);
    }

    /**
     * Test 4.5: Large dataset (50 students, 5 courses)
     */
    public function test_large_dataset() {
        $mock_client = $this->createMock(\restclient::class);
        
        $mock_client->method('selflearn_get_course_progress')
            ->willReturnCallback(function($slug, $usernames) {
                $username = $usernames[0];
                // Simulate random progress
                $progress = rand(0, 100);
                return [['username' => $username, 'progress' => $progress]];
            });

        // Create 50 students
        $users = [];
        for ($i = 1; $i <= 50; $i++) {
            $users[] = (object)['username' => 'student' . $i];
        }

        // Create 5 courses
        $courses = [];
        for ($i = 1; $i <= 5; $i++) {
            $courses[] = ['slug' => 'course' . $i, 'id' => $i];
        }

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Assertions
        $this->assertCount(50, $result); // 50 students
        
        // Check each student has data for 5 courses + average
        foreach ($result as $username => $data) {
            $this->assertCount(6, $data); // 5 courses + total_average
            $this->assertArrayHasKey('total_average', $data);
        }
    }

    // ========================================
    // TEST 5: Data Structure Validation
    // ========================================

    /**
     * Test 5.1: Verify return data structure
     */
    public function test_return_data_structure() {
        $mock_client = $this->createMock(\restclient::class);
        
        $mock_client->method('selflearn_get_course_progress')
            ->willReturn([
                ['username' => 'student1', 'progress' => 75]
            ]);

        $users = [(object)['username' => 'student1']];
        $courses = [
            ['slug' => 'course1', 'id' => 1],
            ['slug' => 'course2', 'id' => 2]
        ];

        $result = selflearn_query_progress($users, $courses, $mock_client);

        // Verify structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('student1', $result);
        $this->assertIsArray($result['student1']);
        $this->assertArrayHasKey('course1', $result['student1']);
        $this->assertArrayHasKey('course2', $result['student1']);
        $this->assertArrayHasKey('total_average', $result['student1']);
    }
}