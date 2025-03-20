<?php
require_once __DIR__ . '/../DatabaseTestCase.php';

class DoctorModelTest extends DatabaseTestCase 
{
    protected $doctorModel;
    protected static $allTestResults = [];
    protected $currentGroup;
    protected static $startTime;
    
    const CURRENT_USER = 'bisosad1501';
    
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/DoctorModel.php';
        $this->doctorModel = new DoctorModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
    }

    private function logSection($title) 
    {
        $this->currentGroup = $title;
        fwrite(STDOUT, "\n" . str_repeat("=", 50) . "\n");
        fwrite(STDOUT, "🔍 {$title}\n");
        fwrite(STDOUT, str_repeat("=", 50) . "\n");
    }

    private function logStep($description, $expected = null)
    {
        fwrite(STDOUT, "\n📋 {$description}\n");
        if ($expected) {
            fwrite(STDOUT, "  Expected: {$expected}\n");
        }
    }

    private function logResult($success, $actual, $error = null) 
    {
        self::$allTestResults[] = [
            'group' => $this->currentGroup,
            'success' => $success,
            'actual' => $actual,
            'error' => $error
        ];

        $icon = $success ? "✅" : "❌";
        $status = $success ? "SUCCESS" : "FAILED";
        
        fwrite(STDOUT, "  Result: {$actual}\n");
        fwrite(STDOUT, "  Status: {$icon} {$status}" . 
            ($error ? " - {$error}" : "") . "\n");
    }


    private function createTestDoctor($override = [])
    {
        return array_merge([
            'email' => 'test_' . time() . '@example.com',
            'phone' => '098' . rand(1000000, 9999999),
            'password' => md5('password123'),
            'name' => 'Test Doctor',
            'description' => 'Test Description',
            'price' => 200000,
            'role' => 'admin',
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s')
        ], $override);
    }

    public function testCRUD()
    {
        $this->logSection("Testing CRUD Operations");
        
        try {
            // Test extend defaults
            $this->logStep("⚙️ Testing default values", "Should set correct defaults for new model");
            $defaultDoctor = new DoctorModel();
            $defaultDoctor->extendDefaults();
    
            $defaultChecks = [
                'email' => '',
                'phone' => '',
                'password' => '',
                'name' => '',
                'description' => '',
                'price' => 0,
                'role' => 'admin',
                'active' => "1",
                'avatar' => '',
                'create_at' => date("Y-m-d H:i:s"),
                'update_at' => date("Y-m-d H:i:s"),
                'speciality_id' => '',
                'room_id' => '',
                'recovery_token' => ''
            ];
            
            $defaultsMatch = true;
            $mismatches = [];

            foreach ($defaultChecks as $field => $expected) {
                $actual = $defaultDoctor->get($field);
                
                if (is_numeric($expected)) {
                    $match = (string)$actual === (string)$expected;
                } else if (strpos($field, '_at') !== false) {
                    $match = DateTime::createFromFormat('Y-m-d H:i:s', $actual) !== false;
                } else {
                    $match = $actual === $expected;
                }

                if (!$match) {
                    $defaultsMatch = false;
                    $mismatches[] = sprintf("  - %s: expected '%s', got '%s'", 
                        $field, $expected, $actual);
                }
            }
    
            $this->logResult($defaultsMatch,
                sprintf("Default values:\n" .
                       "  📊 Status: %s%s",
                       $defaultsMatch ? "All defaults OK" : "Some defaults incorrect",
                       $defaultsMatch ? "" : "\n" . implode("\n", $mismatches)
                ),
                $defaultsMatch ? null : "Default values not set correctly"
            );

            // CREATE
            $this->logStep("📝 Creating new doctor", "Should create doctor with valid data");
            $data = $this->createTestDoctor();
            foreach ($data as $field => $value) {
                $this->doctorModel->set($field, $value);
            }
            
            $id = $this->doctorModel->insert();
            $success = $id > 0;
            
            if ($success) {
                $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $id]);
                $this->assertModelMatchesDatabase($data, TABLE_PREFIX.TABLE_DOCTORS, ['id' => $id]);
            }
            
            $this->logResult($success, 
                "Doctor created " . ($success ? "successfully with ID: {$id}" : "failed"),
                $success ? null : "Failed to create doctor"
            );
            
            self::$allTestResults['Create'] = [
                'success' => $success,
                'message' => $success ? null : "Doctor creation failed"
            ];

            // READ
            $this->logStep("📖 Reading doctor data", "Should retrieve doctor with ID {$id}");
            $doctor = new DoctorModel($id);
            $success = $doctor->isAvailable();
            
            if ($success) {
                $record = $this->getRecord(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $id]);
                $this->assertNotNull($record, "Should find doctor record");
            }
            
            $this->logResult($success, 
                "Doctor data retrieved: " . ($success ? "Yes" : "No"),
                $success ? null : "Failed to read doctor data"
            );
            
            self::$allTestResults['Read'] = [
                'success' => $success,
                'message' => $success ? null : "Doctor read operation failed"
            ];

            // UPDATE
            $this->logStep("📝 Updating doctor name", "Should update name to 'Updated Doctor'");
            $newName = 'Updated Doctor';
            $doctor->set('name', $newName);
            $updateSuccess = $doctor->update();
            
            if ($updateSuccess) {
                $this->assertModelMatchesDatabase(
                    ['name' => $newName],
                    TABLE_PREFIX.TABLE_DOCTORS,
                    ['id' => $id]
                );
            }
            
            $nameMatches = $newName === $doctor->get('name');
            $success = $updateSuccess && $nameMatches;
            
            $this->logResult($success,
                sprintf("Update operation: %s\nName verification: %s", 
                    $updateSuccess ? "Successful" : "Failed",
                    $nameMatches ? "Matches" : "Does not match"
                ),
                $success ? null : "Name update failed"
            );
            
            self::$allTestResults['Update'] = [
                'success' => $success,
                'message' => $success ? null : "Doctor update failed"
            ];

            // DELETE
            $this->logStep("🗑️ Deleting doctor", "Should completely remove doctor");
            $deleteSuccess = $doctor->delete();
            
            if ($deleteSuccess) {
                $this->assertRecordNotExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $id]);
            }
            
            $isGone = !$doctor->isAvailable();
            $success = $deleteSuccess && $isGone;
            
            $this->logResult($success,
                sprintf("Delete operation: %s\nDoctor removed: %s", 
                    $deleteSuccess ? "Successful" : "Failed",
                    $isGone ? "Yes" : "No"
                ),
                $success ? null : "Delete operation failed"
            );
            
            self::$allTestResults['Delete'] = [
                'success' => $success,
                'message' => $success ? null : "Doctor deletion failed"
            ];

        } catch (Exception $e) {
            $this->logResult(false, 
                "❌ Exception occurred during CRUD operations", 
                $e->getMessage()
            );
            self::$allTestResults['CRUD'] = [
                'success' => false, 
                'message' => "CRUD operation failed: " . $e->getMessage()
            ];
        }
    }

    public function testSelectionMethods()
{
    $this->logSection("Testing Selection Methods");
    
    try {
        // Setup test data (not counted as test case)
        $this->logStep("🔧 Setting up test doctor", "Creating doctor with unique identifiers");
        $uniqueTime = time();
        $data = $this->createTestDoctor([
            'email' => "test_{$uniqueTime}@example.com",
            'phone' => "098" . rand(1000000, 9999999)
        ]);
        
        $id = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $data);
        if ($id <= 0) {
            throw new Exception("Failed to setup test data");
        }
        
        $this->logResult(true, 
            sprintf("Test doctor created:\n" .
                   "  📌 ID: %d\n" .
                   "  📧 Email: %s\n" .
                   "  📱 Phone: %s",
                   $id, $data['email'], $data['phone']
            )
        );

        // Track all test results
        $testResults = [];

        // Test 1: ID Selection
        $this->logStep("🔍 Testing ID selection", "Should find doctor with ID {$id}");
        $byId = new DoctorModel($id);
        $idSuccess = $byId->isAvailable();
        $this->logResult($idSuccess, 
            "Selection by ID: " . ($idSuccess ? "✅ Found" : "❌ Not found"),
            $idSuccess ? null : "Failed to find doctor by ID {$id}"
        );
        $testResults['id'] = $idSuccess;

        // Test 2: Email Selection
        $this->logStep("📧 Testing email selection", "Should find doctor with email {$data['email']}");
        $byEmail = new DoctorModel($data['email']);
        $emailSuccess = $byEmail->isAvailable();
        $this->logResult($emailSuccess,
            "Selection by Email: " . ($emailSuccess ? "✅ Found" : "❌ Not found"),
            $emailSuccess ? null : "Failed to find doctor by email {$data['email']}"
        );
        $testResults['email'] = $emailSuccess;

        // Test 3: Phone Selection
        $this->logStep("📱 Testing phone selection", "Should find doctor with phone {$data['phone']}");
        $byPhone = new DoctorModel($data['phone']);
        $phoneSuccess = $byPhone->isAvailable();
        $this->logResult($phoneSuccess,
            "Selection by Phone: " . ($phoneSuccess ? "✅ Found" : "❌ Not found"),
            $phoneSuccess ? null : "Failed to find doctor by phone {$data['phone']}"
        );
        $testResults['phone'] = $phoneSuccess;

        // Test 4: Invalid ID handling
        $this->logStep("⚠️ Testing invalid ID", "Should reject invalid ID");
        $byInvalidId = new DoctorModel(-1);
        $invalidIdHandled = !$byInvalidId->isAvailable();
        $this->logResult($invalidIdHandled,
            "Invalid ID handling: " . ($invalidIdHandled ? "✅ Properly rejected" : "❌ Incorrectly accepted"),
            $invalidIdHandled ? null : "Failed to reject invalid ID"
        );
        $testResults['invalid_id'] = $invalidIdHandled;

        // Test 5: Invalid Email handling
        $this->logStep("⚠️ Testing invalid email", "Should reject invalid email");
        $byInvalidEmail = new DoctorModel("not-an-email");
        $invalidEmailHandled = !$byInvalidEmail->isAvailable();
        $this->logResult($invalidEmailHandled,
            "Invalid email handling: " . ($invalidEmailHandled ? "✅ Properly rejected" : "❌ Incorrectly accepted"),
            $invalidEmailHandled ? null : "Failed to reject invalid email"
        );
        $testResults['invalid_email'] = $invalidEmailHandled;

        // Store single result for Selection Methods group
        self::$allTestResults['Selection Methods'] = [
            'group' => $this->currentGroup,
            'success' => !in_array(false, $testResults),
            'total' => 5, // Number of actual test cases
            'passed' => count(array_filter($testResults)),
            'error' => $phoneSuccess ? null : "Failed to find doctor by phone {$data['phone']}"
        ];

    } catch (Exception $e) {
        $this->logResult(false, "❌ Exception occurred", $e->getMessage());
        self::$allTestResults['Selection Methods'] = [
            'group' => $this->currentGroup,
            'success' => false,
            'error' => "Selection test failed: " . $e->getMessage()
        ];
    }
}

public function testPermissions()
{
    $this->logSection("Testing Permission Methods");
    
    try {
        // Test admin permissions
        $this->logStep("👑 Testing admin role", "Should have admin privileges");
        $adminData = $this->createTestDoctor([
            'role' => 'admin'
        ]);
        $adminId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $adminData);
        $admin = new DoctorModel($adminId);
        
        $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $adminId]);
        
        $adminIsAdmin = $admin->isAdmin();
        $this->logResult($adminIsAdmin, 
            sprintf("Admin privileges check:\n" .
                   "  👤 Role: admin\n" .
                   "  🔑 Admin access: %s",
                   $adminIsAdmin ? "Granted (OK)" : "Denied (ERROR)"
            ),
            $adminIsAdmin ? null : "Admin privileges not granted to admin role"
        );

        // Test regular doctor permissions
        $this->logStep("👨‍⚕️ Testing member role", "Should not have admin privileges");
        $doctorData = $this->createTestDoctor([
            'role' => 'member'
        ]);
        $doctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $doctorData);
        $doctor = new DoctorModel($doctorId);
        
        $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
        
        $doctorIsAdmin = $doctor->isAdmin();
        $this->logResult(!$doctorIsAdmin,
            sprintf("Regular doctor privileges check:\n" .
                   "  👤 Role: member\n" .
                   "  🔑 Admin access: %s",
                   !$doctorIsAdmin ? "Denied (OK)" : "Granted (ERROR)"
            ),
            !$doctorIsAdmin ? null : "Admin privileges incorrectly granted to member role"
        );

        // Test developer role permissions
        $this->logStep("👨‍💻 Testing developer role", "Should have admin privileges");
        $developerData = $this->createTestDoctor([
            'role' => 'developer'
        ]);
        $developerId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $developerData);
        $developer = new DoctorModel($developerId);
        
        $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $developerId]);
        
        $isDeveloper = $developer->isAdmin();
        $this->logResult($isDeveloper,
            sprintf("Developer privileges check:\n" .
                   "  👤 Role: developer\n" .
                   "  🔑 Admin access: %s",
                   $isDeveloper ? "Granted (OK)" : "Denied (ERROR)"
            ),
            $isDeveloper ? null : "Admin privileges not granted to developer role"
        );

        self::$allTestResults['Permissions'] = [
            'success' => $adminIsAdmin && !$doctorIsAdmin && $isDeveloper,
            'message' => ($adminIsAdmin && !$doctorIsAdmin && $isDeveloper) ? 
                        "All permission checks passed" : 
                        "Permission checks failed"
        ];

    } catch (Exception $e) {
        $this->logResult(false, 
            "❌ Exception in permissions test", 
            $e->getMessage()
        );
        self::$allTestResults['Permissions'] = [
            'success' => false,
            'message' => "Permission test failed: " . $e->getMessage()
        ];
    }
}

/**
 * Helper function to generate random string for testing
 */
private function generateRandomString($length = 32) 
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

public function testRecoveryToken()
{
    $this->logSection("Testing Recovery Token");
    
    try {
        // Generate and create test doctor with recovery token
        $this->logStep("🔑 Creating doctor with recovery token", "Should store and validate recovery token");
        $recoveryToken = $this->generateRandomString();
        $data = $this->createTestDoctor([
            'recovery_token' => $recoveryToken
        ]);
        
        $id = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $data);
        $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $id]);
        
        $doctor = new DoctorModel($id);
        
        // Test token validation
        $this->logStep("🔍 Validating recovery token", "Should match stored token");
        $tokenMatch = $doctor->get('recovery_token') === $recoveryToken;
        
        $this->assertModelMatchesDatabase(
            ['recovery_token' => $recoveryToken],
            TABLE_PREFIX.TABLE_DOCTORS,
            ['id' => $id]
        );
        
        $this->logResult($tokenMatch, 
            sprintf("Token validation:\n" .
                   "  🔐 Expected: %s\n" .
                   "  📝 Actual: %s\n" .
                   "  📊 Match: %s",
                   substr($recoveryToken, 0, 8) . '...',
                   substr($doctor->get('recovery_token'), 0, 8) . '...',
                   $tokenMatch ? "Yes (OK)" : "No (ERROR)"
            ),
            $tokenMatch ? null : "Recovery token does not match"
        );
        
        self::$allTestResults['Token Match'] = [
            'success' => $tokenMatch,
            'message' => $tokenMatch ? null : "Token validation failed"
        ];

        // Test token reset
        $this->logStep("🗑️ Testing token reset", "Should clear recovery token");
        $doctor->set('recovery_token', '');
        $updateSuccess = $doctor->update();
        
        if ($updateSuccess) {
            $this->assertModelMatchesDatabase(
                ['recovery_token' => ''],
                TABLE_PREFIX.TABLE_DOCTORS,
                ['id' => $id]
            );
        }
        
        $tokenCleared = $doctor->get('recovery_token') === '';
        $resetSuccess = $updateSuccess && $tokenCleared;
        
        $this->logResult($resetSuccess,
            sprintf("Token reset:\n" .
                   "  📝 Update: %s\n" .
                   "  🔍 Cleared: %s",
                   $updateSuccess ? "Success" : "Failed",
                   $tokenCleared ? "Yes (OK)" : "No (ERROR)"
            ),
            $resetSuccess ? null : "Failed to clear recovery token"
        );
        
        self::$allTestResults['Token Reset'] = [
            'success' => $resetSuccess,
            'message' => $resetSuccess ? null : "Token reset failed"
        ];

        self::$allTestResults['Recovery Token'] = [
            'success' => $tokenMatch && $resetSuccess,
            'message' => ($tokenMatch && $resetSuccess) ? 
                        "Recovery token operations successful" : 
                        "Recovery token operations failed"
        ];

    } catch (Exception $e) {
        $this->logResult(false, 
            "❌ Exception in recovery token test", 
            $e->getMessage()
        );
        self::$allTestResults['Recovery Token'] = [
            'success' => false,
            'message' => "Recovery token test failed: " . $e->getMessage()
        ];
    }
}

public function testActiveStatus()
{
    $this->logSection("Testing Active Status");
    
    try {
        // Test active doctor
        $this->logStep("✅ Testing active doctor", "Should have active status = 1");
        $activeData = $this->createTestDoctor(['active' => 1]);
        $activeId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $activeData);
        
        $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $activeId]);
        
        $activeDoctor = new DoctorModel($activeId);
        $isActive = $activeDoctor->get('active') == 1;
        
        $this->logResult($isActive, 
            sprintf("Active doctor check:\n" .
                   "  👤 ID: %d\n" .
                   "  🔵 Status: %s\n" .
                   "  📊 Result: %s",
                   $activeId,
                   $activeDoctor->get('active'),
                   $isActive ? "Active (OK)" : "Not active (ERROR)"
            ),
            $isActive ? null : "Failed to verify active status"
        );
        
        self::$allTestResults['Active Doctor'] = [
            'success' => $isActive,
            'message' => $isActive ? null : "Active status check failed"
        ];

        // Test inactive doctor
        $this->logStep("⭕ Testing inactive doctor", "Should have active status = 0");
        $inactiveData = $this->createTestDoctor(['active' => 0]);
        $inactiveId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $inactiveData);
        
        $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $inactiveId]);
        
        $inactiveDoctor = new DoctorModel($inactiveId);
        $isInactive = $inactiveDoctor->get('active') == 0;
        
        $this->logResult($isInactive,
            sprintf("Inactive doctor check:\n" .
                   "  👤 ID: %d\n" .
                   "  🔴 Status: %s\n" .
                   "  📊 Result: %s",
                   $inactiveId,
                   $inactiveDoctor->get('active'),
                   $isInactive ? "Inactive (OK)" : "Active (ERROR)"
            ),
            $isInactive ? null : "Failed to verify inactive status"
        );
        
        self::$allTestResults['Inactive Doctor'] = [
            'success' => $isInactive,
            'message' => $isInactive ? null : "Inactive status check failed"
        ];

                // Test status toggle
                $this->logStep("🔄 Testing status toggle", "Should successfully switch between active states");
                $activeDoctor->set('active', 0);
                $toggleSuccess = $activeDoctor->update();
                
                if ($toggleSuccess) {
                    $this->assertModelMatchesDatabase(
                        ['active' => 0],
                        TABLE_PREFIX.TABLE_DOCTORS,
                        ['id' => $activeId]
                    );
                }
                
                $isToggled = $activeDoctor->get('active') == 0;
                
                $this->logResult($isToggled && $toggleSuccess,
                    sprintf("Status toggle check:\n" .
                           "  👤 ID: %d\n" .
                           "  🔄 Operation: %s\n" .
                           "  📊 Result: %s",
                           $activeId,
                           $toggleSuccess ? "Success" : "Failed",
                           $isToggled ? "Toggled correctly" : "Toggle failed"
                    ),
                    ($isToggled && $toggleSuccess) ? null : "Failed to toggle active status"
                );
        
                self::$allTestResults['Active Status'] = [
                    'success' => $isActive && $isInactive && $isToggled && $toggleSuccess,
                    'message' => ($isActive && $isInactive && $isToggled && $toggleSuccess) ? 
                                "Active status tests completed successfully" : 
                                "Active status tests failed"
                ];
        
            } catch (Exception $e) {
                $this->logResult(false, 
                    "❌ Exception in active status test", 
                    $e->getMessage()
                );
                self::$allTestResults['Active Status'] = [
                    'success' => false, 
                    'message' => "Active status test failed: " . $e->getMessage()
                ];
            }
        }

        protected function tearDown()
{
    parent::tearDown();

    // Get current test name
    $currentTest = $this->getName();
    
    // Get all test methods
    $class = new ReflectionClass($this);
    $methods = array_filter($class->getMethods(), function($method) {
        return strpos($method->name, 'test') === 0;
    });
    
    // Get last test name
    $lastTest = end($methods)->name;
    
    // Print summary if this is the last test
    if ($currentTest === $lastTest) {
        $this->printFinalSummary();
    }

    if ($this->pdo && $this->pdo->inTransaction()) {
        $this->pdo->rollBack();
    }
}



private function printFinalSummary()
{
    if (empty(self::$allTestResults)) {
        return;
    }

    // Header output
    fwrite(STDOUT, "\n" . str_repeat("=", 70) . "\n");
    fwrite(STDOUT, "📊 FINAL TEST SUMMARY\n");
    fwrite(STDOUT, str_repeat("=", 70) . "\n");
    fwrite(STDOUT, "🕒 Current Time: " . date('Y-m-d H:i:s') . "\n");
    fwrite(STDOUT, "👤 User: " . self::CURRENT_USER . "\n\n");

    // Define correct test counts for each group
    $testCounts = [
        'Testing CRUD Operations' => 5,
        'Testing Selection Methods' => 5,  // Fixed count
        'Testing Permission Methods' => 3,
        'Testing Recovery Token' => 2,
        'Testing Active Status' => 3
    ];

    $groupResults = [];
    $totalTests = 0;
    $totalPassed = 0;

    // Initialize group results with correct counts
    foreach ($testCounts as $group => $count) {
        $groupResults[$group] = [
            'total' => $count,
            'passed' => 0,
            'failures' => []
        ];
        $totalTests += $count;
    }

    // Count passed tests and collect failures
    foreach (self::$allTestResults as $key => $result) {
        if (!isset($result['group'])) continue;
        
        $group = $result['group'];
        if (!isset($groupResults[$group])) continue;

        // For Selection Methods, only count the actual test results
        if ($group === 'Testing Selection Methods') {
            if (isset($result['total']) && isset($result['passed'])) {
                $groupResults[$group]['passed'] = $result['passed'];
                $totalPassed += $result['passed'];
                if (isset($result['error']) && $result['error']) {
                    $groupResults[$group]['failures'][] = $result['error'];
                }
                continue;
            }
        } else {
            // For other groups, count as before
            if ($result['success']) {
                $groupResults[$group]['passed']++;
                $totalPassed++;
            }
            if (!$result['success'] && isset($result['message']) && $result['message']) {
                $groupResults[$group]['failures'][] = $result['message'];
            }
        }
    }

    // Print group results
    foreach ($groupResults as $group => $stats) {
        fwrite(STDOUT, "GROUP: {$group}\n");
        fwrite(STDOUT, sprintf("  ✓ Passed: %d/%d (%d%%)\n",
            $stats['passed'],
            $stats['total'],
            ($stats['total'] > 0) ? ($stats['passed'] / $stats['total'] * 100) : 0
        ));

        if (!empty($stats['failures'])) {
            fwrite(STDOUT, "  ✗ Failures:\n");
            foreach (array_unique($stats['failures']) as $failure) {
                if ($failure) {
                    fwrite(STDOUT, "    • {$failure}\n");
                }
            }
        }
        fwrite(STDOUT, "\n");
    }

    // Print overall statistics
    $duration = round(microtime(true) - self::$startTime, 2);
    fwrite(STDOUT, str_repeat("-", 70) . "\n");
    fwrite(STDOUT, "OVERALL STATISTICS\n");
    fwrite(STDOUT, sprintf("✅ Total Tests: %d\n", $totalTests));
    fwrite(STDOUT, sprintf("✅ Passed: %d (%d%%)\n", 
        $totalPassed, 
        ($totalTests > 0) ? floor(($totalPassed / $totalTests) * 100) : 0
    ));
    fwrite(STDOUT, sprintf("❌ Failed: %d\n", $totalTests - $totalPassed));
    fwrite(STDOUT, sprintf("⏱️ Duration: %.2fs\n", $duration));
    fwrite(STDOUT, str_repeat("=", 70) . "\n\n");
}
}