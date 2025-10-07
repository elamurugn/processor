<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'tree_oxygen_db';
$username = 'root';
$password = '';

// Initialize database connection
function getDBConnection() {
    global $host, $dbname, $username, $password;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize session variables
if (!isset($_SESSION['current_step'])) {
    $_SESSION['current_step'] = 1;
    $_SESSION['filtered_trees'] = [];
    $_SESSION['processing_params'] = [];
}

// Processing steps configuration
$processing_steps = [
    1 => ['type' => 'select', 'name' => 'Land Forms', 'python_script' => 'tree_land_form.py', 'param_id' => 'land_form'],
    2 => ['type' => 'select', 'name' => 'Soil Type', 'python_script' => 'tree_soil_type.py', 'param_id' => 'soil_type'],
    3 => ['type' => 'range', 'name' => 'Soil Salt Range', 'python_script' => 'tree_soil_salt_range.py', 'param_id' => 'soil_salt', 'min' => 0.1, 'max' => 5.0, 'unit' => 'EC (dS/m)'],
    4 => ['type' => 'range', 'name' => 'Soil Pollution Range', 'python_script' => 'tree_soil_pollution_range.py', 'param_id' => 'soil_pollution', 'min' => 1, 'max' => 10, 'unit' => 'AQI'],
    5 => ['type' => 'range', 'name' => 'Soil Drainage Range', 'python_script' => 'tree_soil_drainage_range.py', 'param_id' => 'soil_drainage', 'min' => 1, 'max' => 5, 'unit' => 'Scale'],
    6 => ['type' => 'range', 'name' => 'Erosion Tolerance Range', 'python_script' => 'tree_erosion_tolerance_range.py', 'param_id' => 'erosion_tolerance', 'min' => 1, 'max' => 10, 'unit' => 'Resistance'],
    7 => ['type' => 'range', 'name' => 'Soil pH Level', 'python_script' => 'tree_soil_ph_level.py', 'param_id' => 'soil_ph', 'min' => 3.5, 'max' => 9.0, 'unit' => 'pH'],
    8 => ['type' => 'range', 'name' => 'Water pH Level', 'python_script' => 'tree_water_ph_level.py', 'param_id' => 'water_ph', 'min' => 3.5, 'max' => 9.0, 'unit' => 'pH'],
    9 => ['type' => 'range', 'name' => 'Rainfall Range', 'python_script' => 'tree_rainfall_range.py', 'param_id' => 'rainfall', 'min' => 200, 'max' => 4000, 'unit' => 'mm/year'],
    10 => ['type' => 'range', 'name' => 'Flood Tolerance Range', 'python_script' => 'tree_flood_tolerance_range.py', 'param_id' => 'flood_tolerance', 'min' => 1, 'max' => 10, 'unit' => 'Level'],
    11 => ['type' => 'range', 'name' => 'Groundwater Depth Range', 'python_script' => 'tree_groundwater_depth_range.py', 'param_id' => 'groundwater_depth', 'min' => 0.5, 'max' => 50, 'unit' => 'meters'],
    12 => ['type' => 'range', 'name' => 'Humidity Range', 'python_script' => 'tree_humidity_range.py', 'param_id' => 'humidity', 'min' => 30, 'max' => 95, 'unit' => '%'],
    13 => ['type' => 'range', 'name' => 'Air Pollution Tolerance Range', 'python_script' => 'tree_air_pollution_tolerance_range.py', 'param_id' => 'air_pollution_tolerance', 'min' => 1, 'max' => 10, 'unit' => 'AQI'],
    14 => ['type' => 'range', 'name' => 'Wind Speed Range', 'python_script' => 'tree_wind_speed_range.py', 'param_id' => 'wind_speed', 'min' => 0, 'max' => 50, 'unit' => 'km/h'],
    15 => ['type' => 'range', 'name' => 'Temperature Range', 'python_script' => 'tree_temperature_range.py', 'param_id' => 'temperature', 'min' => -20, 'max' => 50, 'unit' => '°C'],
    16 => ['type' => 'range', 'name' => 'Light Intensity Range', 'python_script' => 'tree_light_intensity_range.py', 'param_id' => 'light_intensity', 'min' => 100, 'max' => 100000, 'unit' => 'lux'],
    17 => ['type' => 'range', 'name' => 'UVA Light Range', 'python_script' => 'tree_uva_light.py', 'param_id' => 'uva_light', 'min' => 0, 'max' => 100, 'unit' => 'µW/cm²'],
    18 => ['type' => 'range', 'name' => 'UVB Light Range', 'python_script' => 'tree_uvb_light.py', 'param_id' => 'uvb_light', 'min' => 0, 'max' => 50, 'unit' => 'µW/cm²'],
    19 => ['type' => 'range', 'name' => 'Red/Far-Red Light Range', 'python_script' => 'tree_red_farred_light.py', 'param_id' => 'red_farred_light', 'min' => 0.5, 'max' => 2.0, 'unit' => 'ratio'],
    20 => ['type' => 'range', 'name' => 'Blue Light Range', 'python_script' => 'tree_blue_light.py', 'param_id' => 'blue_light', 'min' => 10, 'max' => 500, 'unit' => 'µmol/m²/s'],
    21 => ['type' => 'range', 'name' => 'Green Light Range', 'python_script' => 'tree_green_light.py', 'param_id' => 'green_light', 'min' => 5, 'max' => 200, 'unit' => 'µmol/m²/s'],
    22 => ['type' => 'range', 'name' => 'Infrared Light Range', 'python_script' => 'tree_infrared_light.py', 'param_id' => 'infrared_light', 'min' => 50, 'max' => 1000, 'unit' => 'W/m²'],
    23 => ['type' => 'range', 'name' => 'Direct Sunlight Range', 'python_script' => 'tree_direct_sunlight.py', 'param_id' => 'direct_sunlight', 'min' => 2, 'max' => 12, 'unit' => 'hours/day'],
    24 => ['type' => 'display', 'name' => 'Final Results', 'python_script' => '', 'param_id' => 'final_results']
];

// Handle form submission
if ($_POST) {
    $current_step = $_SESSION['current_step'];
    $step_config = $processing_steps[$current_step];
    
    if ($step_config['type'] === 'select') {
        $selected_value = $_POST['selection'] ?? '';
        if ($selected_value) {
            $_SESSION['processing_params'][$step_config['param_id']] = $selected_value;
            
            // Call Python script for processing
            $filtered_trees = callPythonProcessor($step_config['python_script'], $selected_value, $_SESSION['filtered_trees']);
            $_SESSION['filtered_trees'] = $filtered_trees;
            
            // Move to next step
            $_SESSION['current_step']++;
        }
    } elseif ($step_config['type'] === 'range') {
        $from_value = floatval($_POST['from_value'] ?? 0);
        $to_value = floatval($_POST['to_value'] ?? 0);
        
        if ($from_value > 0 && $to_value > $from_value && 
            $from_value >= $step_config['min'] && $to_value <= $step_config['max']) {
            
            $_SESSION['processing_params'][$step_config['param_id']] = [
                'from' => $from_value,
                'to' => $to_value
            ];
            
            // Call Python script for processing
            $filtered_trees = callPythonProcessor($step_config['python_script'], ['from' => $from_value, 'to' => $to_value], $_SESSION['filtered_trees']);
            $_SESSION['filtered_trees'] = $filtered_trees;
            
            // Move to next step
            $_SESSION['current_step']++;
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Call Python processor function
function callPythonProcessor($script_name, $input_value, $current_tree_list) {
    $python_script_path = "python/" . $script_name;
    
    if (!file_exists($python_script_path)) {
        return $current_tree_list; // Return current list if script not found
    }
    
    // Prepare input data for Python script
    $input_data = [
        'input_value' => $input_value,
        'current_tree_list' => $current_tree_list
    ];
    
    // Execute Python script
    $command = "python3 " . escapeshellarg($python_script_path) . " " . escapeshellarg(json_encode($input_data));
    $output = shell_exec($command);
    
    if ($output) {
        $result = json_decode(trim($output), true);
        return $result['filtered_trees'] ?? [];
    }
    
    return $current_tree_list;
}

// Get land forms for dropdown
function getLandForms() {
    return [
        'LF01' => 'River Valley - Fertile lowland area alongside rivers',
        'LF02' => 'Mountain Valley - High altitude valley between mountains', 
        'LF03' => 'Glacial Valley - U-shaped valley formed by glaciers',
        'LF04' => 'Coastal Plain - Flat land adjacent to ocean',
        'LF05' => 'Prairie - Grassland plain with deep fertile soil',
        'LF06' => 'Floodplain - Low-lying area adjacent to rivers',
        'LF07' => 'Rolling Hills - Gently undulating terrain',
        'LF08' => 'Foothills - Elevated land at mountain base',
        'LF09' => 'Plateau - Elevated flat-topped landform',
        'LF10' => 'Canyon - Deep narrow valley with steep sides'
    ];
}

// Get soil types for dropdown
function getSoilTypes() {
    return [
        'ST01' => 'Sandy Soil - Well-draining, low water retention',
        'ST02' => 'Clay Soil - Poor drainage, high water retention',
        'ST03' => 'Loamy Soil - Balanced drainage and nutrients',
        'ST04' => 'Silt Soil - Fine particles, good water retention',
        'ST05' => 'Peat Soil - High organic matter, acidic',
        'ST06' => 'Chalky Soil - Alkaline, well-draining',
        'ST07' => 'Saline Soil - High salt content',
        'ST08' => 'Alkaline Soil - High pH, poor nutrient availability',
        'ST09' => 'Acidic Soil - Low pH, high acidity',
        'ST10' => 'Waterlogged Soil - Poor drainage, anaerobic conditions',
        'ST11' => 'Rocky Soil - High stone content, poor water retention',
        'ST12' => 'Organic Soil - High organic matter content',
        'ST13' => 'Compacted Soil - Dense, poor root penetration',
        'ST14' => 'Volcanic Soil - Rich in minerals, well-draining',
        'ST15' => 'Desert Soil - Low organic matter, high sand content',
        'ST16' => 'Tropical Soil - High weathering, low nutrients',
        'ST17' => 'Permafrost Soil - Permanently frozen subsoil',
        'ST18' => 'Low-Activity Clay Soil (Lixisols) - Weathered clay with low nutrient retention'
    ];
}

$current_step = $_SESSION['current_step'];
$step_config = $processing_steps[$current_step] ?? null;
?>

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Species Environmental Filter - Step <?php echo $current_step; ?></title>
    <link rel="stylesheet" href="processor.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="main-content">
            <!-- Progress Sidebar -->
            <aside class="progress-sidebar">
                <div class="progress-card">
                    <div class="progress-card-header">
                        <h3>Processing Steps</h3>
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L13.09 6.26L17 4L14.74 8.04L19 8L15.96 11.26L20 12L15.96 12.74L19 16L14.74 15.96L17 20L13.09 17.74L12 22L10.91 17.74L7 20L9.26 15.96L5 16L8.04 12.74L4 12L8.04 11.26L5 8L9.26 8.04L7 4L10.91 6.26L12 2Z" fill="#22C55E"/>
                        </svg>
                    </div>
                    <div class="progress-summary-sidebar">
                        <span class="step-counter">Step <?php echo $current_step; ?> of 24</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo ($current_step / 24) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div class="steps-list">
                        <?php foreach ($processing_steps as $step_num => $step_info): ?>
                            <div class="step-item <?php echo $step_num < $current_step ? 'completed' : ($step_num == $current_step ? 'active' : ''); ?>" data-testid="step-<?php echo $step_num; ?>">
                                <div class="step-number"><?php echo $step_num; ?></div>
                                <div class="step-content">
                                    <div class="step-name"><?php echo $step_info['name']; ?></div>
                                    <?php if ($step_info['type'] === 'range' && isset($step_info['unit'])): ?>
                                        <div class="step-desc"><?php echo $step_info['min'] . ' - ' . $step_info['max'] . ' ' . $step_info['unit']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <!-- Main Form Area -->
            <main class="form-area">
                <?php if ($current_step <= 24 && $step_config): ?>
                    <div class="form-card">
                        <div class="form-header">
                            <h2>Step <?php echo $current_step; ?>: <?php echo $step_config['name']; ?></h2>
                            <p class="form-description">
                                <?php if ($step_config['type'] === 'select'): ?>
                                    Select the appropriate option from the dropdown menu below.
                                <?php else: ?>
                                    Enter the range values within the specified limits.
                                <?php endif; ?>
                            </p>
                        </div>

                        <form method="POST" class="processing-form" data-testid="form-section">
                            <?php if ($step_config['type'] === 'select'): ?>
                                <!-- Single Selection Input -->
                                <div class="form-group">
                                    <label for="selection" class="form-label">
                                        <?php echo $step_config['name']; ?> <span class="required">*</span>
                                    </label>
                                    <select name="selection" id="selection" class="form-select" required data-testid="select-<?php echo $step_config['param_id']; ?>">
                                        <option value="">Select <?php echo $step_config['name']; ?>...</option>
                                        <?php 
                                        $options = ($current_step == 1) ? getLandForms() : getSoilTypes();
                                        foreach ($options as $id => $description): 
                                        ?>
                                            <option value="<?php echo $id; ?>"><?php echo $description; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-help">
                                        <?php if ($current_step == 1): ?>
                                            Example: Canyon (LF10) - Deep narrow valley with steep sides
                                        <?php else: ?>
                                            Example: Low-Activity Clay Soil (ST18) - Weathered clay with low nutrient retention
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php elseif ($step_config['type'] === 'range'): ?>
                                <!-- Range Input -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <?php echo $step_config['name']; ?> <span class="required">*</span>
                                    </label>
                                    <div class="range-inputs">
                                        <div class="range-input-group">
                                            <label for="from_value" class="range-label">From</label>
                                            <input type="number" 
                                                   name="from_value" 
                                                   id="from_value" 
                                                   class="form-input range-input" 
                                                   min="<?php echo $step_config['min']; ?>" 
                                                   max="<?php echo $step_config['max']; ?>" 
                                                   step="0.1" 
                                                   required
                                                   data-testid="input-<?php echo $step_config['param_id']; ?>-from">
                                            <div class="range-note" id="from-note">
                                                Range: <?php echo $step_config['min']; ?> - <?php echo $step_config['max']; ?> <?php echo $step_config['unit']; ?>
                                            </div>
                                        </div>
                                        <div class="range-input-group">
                                            <label for="to_value" class="range-label">To</label>
                                            <input type="number" 
                                                   name="to_value" 
                                                   id="to_value" 
                                                   class="form-input range-input" 
                                                   min="<?php echo $step_config['min']; ?>" 
                                                   max="<?php echo $step_config['max']; ?>" 
                                                   step="0.1" 
                                                   required
                                                   data-testid="input-<?php echo $step_config['param_id']; ?>-to">
                                            <div class="range-note" id="to-note">
                                                Must be higher than 'From' value
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-help">
                                        Enter values within the range <?php echo $step_config['min']; ?> - <?php echo $step_config['max']; ?> <?php echo $step_config['unit']; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="form-actions">
                                <?php if ($current_step > 1): ?>
                                    <button type="button" class="btn btn-secondary" onclick="goToPreviousStep()" data-testid="button-previous">
                                        ← Previous Step
                                    </button>
                                <?php endif; ?>
                                
                                <button type="submit" class="btn btn-primary" data-testid="button-process-step-<?php echo $current_step; ?>">
                                    Process Step <?php echo $current_step; ?> →
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Results Display -->
                    <?php if (!empty($_SESSION['filtered_trees'])): ?>
                        <div class="results-card" data-testid="tree-results">
                            <div class="results-header">
                                <h3>Filtered Trees</h3>
                                <p>Trees matching your current criteria</p>
                            </div>
                            <div class="results-content">
                                <div class="trees-count">
                                    <span class="count-number" data-testid="total-count"><?php echo count($_SESSION['filtered_trees']); ?></span>
                                    trees match current criteria
                                </div>
                                <div class="trees-list">
                                    <?php foreach (array_slice($_SESSION['filtered_trees'], 0, 10) as $tree): ?>
                                        <div class="tree-item" data-testid="tree-result-<?php echo $tree['tree_id'] ?? ''; ?>">
                                            <div class="tree-info">
                                                <div class="tree-name"><?php echo $tree['common_name'] ?? 'Unknown'; ?></div>
                                                <div class="tree-scientific"><?php echo $tree['scientific_name'] ?? ''; ?></div>
                                            </div>
                                            <div class="tree-id"><?php echo $tree['tree_id'] ?? ''; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($_SESSION['filtered_trees']) > 10): ?>
                                        <div class="more-trees">
                                            ... and <?php echo count($_SESSION['filtered_trees']) - 10; ?> more trees
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Final Results Page -->
                    <div class="final-results-card">
                        <div class="results-header">
                            <h2>🎉 Processing Complete!</h2>
                            <p>Your tree selection has been filtered through all 24 environmental parameters</p>
                        </div>
                        <div class="final-results-content">
                            <div class="results-summary">
                                <div class="summary-stat">
                                    <div class="stat-number"><?php echo count($_SESSION['filtered_trees']); ?></div>
                                    <div class="stat-label">Trees Selected</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="stat-number">24</div>
                                    <div class="stat-label">Parameters Processed</div>
                                </div>
                            </div>
                            
                            <div class="final-actions">
                                <button type="button" class="btn btn-primary" onclick="proceedToNextPage()">
                                    Proceed to Tree Details →
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetProcess()">
                                    Start New Selection
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>

            <!-- Info Sidebar -->
            <aside class="info-sidebar" data-testid="info-sidebar">
                <div class="info-card">
                    <h3>Processing Information</h3>
                    
                    <div class="info-section">
                        <h4>Sequential Processing</h4>
                        <p>Each parameter filters the tree list sequentially. Processing occurs one step at a time for optimal accuracy.</p>
                    </div>

                    <div class="info-section">
                        <h4>Validation Rules</h4>
                        <ul>
                            <li>Range inputs must be within specified limits</li>
                            <li>'To' value must exceed 'From' value</li>
                            <li>Real-time feedback for invalid entries</li>
                        </ul>
                    </div>

                    <div class="info-section">
                        <h4>Database Integration</h4>
                        <p>Python scripts process environmental parameters against comprehensive tree database with 24 filtering criteria.</p>
                    </div>

                    <?php if ($step_config && $step_config['type'] === 'range'): ?>
                        <div class="info-section current-param">
                            <h4>Current Parameter</h4>
                            <div class="param-details">
                                <div class="param-name"><?php echo $step_config['name']; ?></div>
                                <div class="param-range">Range: <?php echo $step_config['min']; ?> - <?php echo $step_config['max']; ?> <?php echo $step_config['unit']; ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>

    <script src="processor.js"></script>
</body>
</html>
