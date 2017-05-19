<!DOCTYPE html>
<html>
    <head>
        <title>Popeyes</title>
        <?php
            $style_path = 'css/style.css';
            $version = filemtime($style_path);
            echo "<link rel='stylesheet' href='$style_path?ver=$version'>";
        ?>
    </head>
    
    <body>
        <?php
            class Food {
                public $name;
                public $calories;
                public $fat;
                public $sodium;
                
                function __construct($name="", $calories=null, $fat=null, $sodium=null) {
                    $this->name = $name;
                    $this->calories = $calories;
                    $this->fat = $fat;
                    $this->sodium = $sodium;
                }
                
                function validate_name() {
                    $name = filter_var($this->name, FILTER_SANITIZE_STRING);
                    if (ctype_alpha(str_replace(' ', '', $name))) {
                        return true;
                    } else {
                        echo '<p>Invalid name</p>';
                        return false;
                    }
                }
        
               function validate_calories() {
                    $calories = filter_var($this->calories, FILTER_VALIDATE_INT);
                    if (is_numeric($calories)) {
                        if ($calories >= 0 && $calories <= 1500)   // japenos are 0 calories, the most caloric item has 1190. 
                            return true;         // I can't imagine items going higher than 1500
                        else {
                            echo '<p>Invalid calorie entry</p>';
                            return false;
                        }
                    } else {
                        echo '<p>Did not enter a number for calories</p>';
                        return false; 
                    }
                }

                function validate_fat() {
                    $fat = filter_var($this->fat, FILTER_VALIDATE_INT);
                    if (is_numeric($fat)) {
                        if ($fat >= 0 && $fat <= 120) {
                            return true;
                        } else {
                            print "<p>Invalid fat</p>";
                            return false;
                        }
                    } else {
                        print "<p>Did not enter a number for fat</p>";
                        return false;
                    }
                }

                function validate_sodium() {
                    $sodium = filter_var($this->sodium, FILTER_VALIDATE_INT);
                    if (is_numeric($sodium)) {
                        if ($sodium >= 0 && $sodium <= 2500) {
                            return true;
                        } else {
                            echo '<p>Invalid sodium</p>';
                            return false;
                        }
                    } else {
                        echo '<p>Did not enter a number for sodium</p>';
                        return false;
                    }
                }
            }
        // **************************************************************** //
        // READ DATA FILE
        
            $data_file = 'popeyes_data.txt';
            
            if (!file_exists($data_file)) {
                echo "<p>Can't find the data</p>";
                exit;
            }
        
            $file_pointer = fopen($data_file, 'r');
            
            if (!$file_pointer) {
                echo '<p>Error reading file</p>';
                exit;
            }

            $food = array(); // stores all food items from the data file

            while(!feof($file_pointer)) {
                $line = fgets($file_pointer);
                
                if ($line != "") {
                    $food_item_string = explode("\t", $line);
                    if (!empty($food_item_string)) {
                        $food_item = new Food($food_item_string[0], $food_item_string[1], $food_item_string[2], intval($food_item_string[3]));
                        $food[] = $food_item;
                    }
                }
            }

            unset($food_item);
            unset($line);
            fclose($file_pointer);

            if (!is_array($food)) {
                echo '<p>Error reading file</p>';
                exit;
            }
        
        // **************************************************************** //
        // ADD NEW ITEM //
        
            if (isset($_POST['submit'])) {                
                $food_item = new Food($_POST['name'], $_POST['calories'], $_POST['fat'], $_POST['sodium']);

                if (isDuplicate($food_item, $food)) {
                    echo '<p>Duplicate item</p>';
                }
                    
                elseif ($food_item->validate_name() && $food_item->validate_calories() && $food_item->validate_fat() && $food_item->validate_sodium()) {
                    $food[] = $food_item;
                        
                    $file_pointer = fopen($data_file, 'a');
                    if (!$file_pointer) {
                        echo "<p>Can't open $file_pointer for saving</p>";
                        exit;
                    } 

                    $to_save = $food_item->name . "\t" . $food_item->calories . "\t" . $food_item->fat . "\t" . $food_item->sodium . "\n";
                    fwrite($file_pointer, $to_save);

                    $closed = fclose($file_pointer);
                    if ($closed) {
                        echo '<p>Saved successfully</p>';
                    } else {
                        echo '<p>Saved unsuccessfully</p>';
                    }
                }
            }

            function isDuplicate($add_food, $food_array) {
                $flag = false;

                foreach($food_array as $index => $food_item) {
                    $flag = ($flag || 
                           ($food_item->name == $add_food->name && 
                           $food_item->calories == $add_food->calories && 
                           $food_item->fat == $add_food->fat && 
                           $food_item->sodium == $add_food->sodium)
                           );
                }

                return $flag;
            }
        
        // **************************************************************** //
        // CLEAR DATA FILE //
        
            if (isset($_POST['reset'])) {
                $file_pointer = fopen($data_file, 'w');
                
                if (!$file_pointer) {
                    echo '<p>Error reading file</p>';
                    exit;
                }
                
                fwrite($file_pointer, "");
                $food = [];
                
                $closed = fclose($file_pointer);
                if ($closed) {
                    echo '<p>Reset</p>';
                } else {
                    echo '<p>Unable to reset</p>';
                }
            }
        ?>
        
        <form method="post">
            <div id="functions">
                <input type="text" name="search_name" placeholder="Name">
                <input type="text" name="search_calories" placeholder="Calories">
                <input type="text" name="search_fat" placeholder="Fat">
                <input type="text" name="search_sodium" placeholder="Sodium">
                <input type="submit" name="search" value="Search">
                
                <input type="submit" name="show" value="Show All">
                <input type="submit" name="reset" value="Reset">
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Calories</th>
                        <th>Fat</th>
                        <th>Sodium</th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php
                        //Display table normally
                        if (!isset($_POST['search']) ) {
                            foreach($food as $i => $food_item) {
                                printFoodItem($food_item);
                            }
                        } 
                    
                        // Searched tabled
                        elseif (isset($_POST['search'])) {
                            $search_elements = getSearchElements();
                            print "<br><p>Displaying search results for ";
                            $counter = 0;
                            foreach ($search_elements as $key => $value) {
                                $counter++;
                                if ($key == "name") {
                                    print $value;
                                } else {
                                    print $value . "g of " . $key;
                                }
                                
                                if ($counter < count($search_elements)) {
                                    print ', ';
                                } else {
                                    print ' ';
                                }
                            }
                            print "</p>";

                            foreach($food as $i => $food_item) {
                                $shouldPost = true;
                                foreach($search_elements as $key => $value) {
                                    if ($key == "name") {
                                        $name = strtolower($food_item->name);
                                        if (strpos($name, $value) === false) {
                                            $shouldPost = false;
                                            break;
                                        }
                                    } 
                                    if ($key == "calories") {
                                        if ($value < $food_item->calories) { 
                                            $shouldPost = false;
                                            break;
                                        }
                                        
                                    }
                                    if ($key == "fat") {
                                        print "in fat";
                                        if ($value < $food_item->fat) {
                                            $shouldPost = false;
                                            break;
                                        }
                                    }
                                    if ($key == "sodium") {
                                        if ($value < $food_item->sodium) {
                                            $shouldPost = false;
                                            break;
                                        }
                                    }
                                }
                                if ($shouldPost) 
                                    printFoodItem($food_item);
                            }
                        }

                        function printFoodItem($food_item) {
                            echo "<tr>";
                            foreach($food_item as $i => $nutrition) {
                                $nutrition_item = htmlentities($nutrition);
                                echo "<td>$nutrition_item</td>";
                            }
                            echo "</tr>";
                        }
                    
                        function getSearchElements() {
                            print 'here';
                            $search_elements = array();
                            if (!empty($_POST['search_name'])) {
                                $search_name = strtolower(filter_input(INPUT_POST, 'search_name', FILTER_SANITIZE_STRING));
                                $search_elements['name'] = $search_name;
                            }
                            if (!empty($_POST['search_calories'])) {
                                $search_calories = filter_input(INPUT_POST, 'search_calories', FILTER_VALIDATE_INT);
                                $search_elements['calories'] = $search_calories;
                            }
                            if (!empty($_POST['search_fat'])) {
                                $search_fat = filter_input(INPUT_POST, 'search_fat', FILTER_VALIDATE_INT);
                                $search_elements['fat'] = $search_fat;
                            }
                            if (!empty($_POST['search_sodium'])) {
                                $search_sodium = filter_input(INPUT_POST, 'search_sodium', FILTER_VALIDATE_INT);
                                $search_elements['sodium'] = $search_sodium;
                            }
                            
                            return $search_elements;
                        }
                    ?>
                
                    <tr>
                        <td>
                            <input type="text" name="name" maxlength="80" placeholder="name">
                        </td>
                        <td>
                            <input type="text" name="calories" maxlength="80" placeholder="calories">
                        </td>
                        <td>
                            <input type="text" name="fat" maxlength="80" placeholder="fat">
                        </td>
                        <td>
                            <input type="text" name="sodium" maxlength="80" placeholder="sodium">
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div id="add">
                <input type="submit" name="submit" value="Add" id="add">
            </div>
        </form>
    </body>
</html>