<?PHP
// Take the report card data and publish it, to the database and a javascript file.
// We do this with PHP because Centos 5.
//
// How it works:
//  1. Check if the card is in the database
//  2. If it's not, add the card to the db
//  3. If it's not, write the card javascript file and FTP it to extras

date_default_timezone_set('America/Denver');
$connection = array(
    'user' => file_get_contents('.mysql_user'),
    'password' => file_get_contents('.mysql_pass'),
    'host' => file_get_contents('.mysql_host'),
    'db' => 'reportcard');
$db = new mysqli($connection['host'], $connection['user'], $connection['password'], $connection['db']);
if ( $db->connect_errno )
{
    //die('Could not connect to database: ' . $db->connect_error);
}

function clean_string($value, $quote_char='"')
{
    // Clean a string so it's suitable for writing in a javascript file.
    // Defaults to escaping out for double quotes.
    $string = str_replace("\n", '', $string);
    if ( $quote_char == '"' ):
        $string = str_replace('"', '\"', $string);
    elseif ( $quote_char == "'" ):
        $string = str_replace("'", "\'", $string);
    endif;
    return $string;
}

$csv = array_map('str_getcsv', file('records.csv'));
$key = $csv[0];
$i = 0;
foreach ( $csv as $item ):
    $i++;
    if ( $i == 1 )
        continue;
    // Use the values of the first row as the keys in a new associative array:
    // Should result in an array looking something like:
    // array(6) {
    //  ["Timestamp"]=>
    //  string(17) "9/5/2014 11:49:38"
    //  ["Title"]=>
    //  string(30) "Denver Broncos Offense, Week 1"
    //  ["Description"]=>
    //  string(69) "Rate the Denver Broncos Offense in the first game of the 2014 season."
    //  ["slug"]=>
    //  string(34) "denver-broncos-offense-week-1-2014"
    //  ["Date expires"]=>
    //  string(0) ""
    //  ["Date launches"]=>
    //  string(8) "9/7/2014"
    //}
    $record = array_combine($key, $item);

    // Check if the card exists in the database:
    $sql = 'SELECT id FROM cards WHERE slug = "' . $record['slug'] . '" LIMIT 1';
    $result = $db->query($sql);

    // If it exists we don't do anything. If it doesn't, we add it to the db
    // and write / ftp a javascript representation of this data to a production server.
    if ( mysqli_num_rows($result) == 0 ):
        
        $date_expire = '';
        $date_launch = '';
        if ( trim($record['Date expires']) != '' )
            $date_expire = strftime('%Y-%m-%d', strtotime($record['Date expires']));
        if ( trim($record['Date launches']) != '' )
            $date_launch = strftime('%Y-%m-%d', strtotime($record['Date launches']));

        $sql = 'INSERT INTO cards (slug, title, description, date_launch, date_expire, grade_average, grades) 
                VALUES
                ("' . $record['slug'] . '", "' . $record['Title'] . '", "' . $record['Description'] . '", "' . $date_launch . '", "' . $date_expire . '", 0, 0)';
        //$result = $db->query($sql);

        // Now we write the file
        $content = '
var ' . str_replace('-', '_', $record['slug']) . ' = {
    title: "' . clean_string($record['Title']) . '",
    description: "' . clean_string($record['Description']) . '",
    slug: "' . str_replace('-', '_', $record['slug']) . '",
    date_launch: "' . $record['Date launches'] . '",
    date_expire: "' . $record['Date expires'] . '"
    };';
        echo $content;
    endif;
endforeach;
