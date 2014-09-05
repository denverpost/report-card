<?PHP
// Take the report card data and publish it, to the database and a javascript file.
// We do this with PHP because Centos 5.
//
// How it works:
//  1. Check if the card is in the database
//  2. If it's not, add the card to the db
//  3. If it's not, write the card javascript file and FTP it to extras

// VIM note: To make a large chunk of markup javascript-friendly,
// I appended the literal string "\n\" before each newline character with this search and replace:
// :92,110s/\n/\\n\\\r/
// where 92 and 110 are the lines to start replace and the lines to end.

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

function clean_string($string, $quote_char='"')
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
        $result = $db->query($sql);

        // Now we write the file
        $slug = str_replace('-', '_', $record['slug']);
        $content = '
var ' . $slug . ' = {
    title: "' . clean_string($record['Title']) . '",
    description: "' . clean_string($record['Description']) . '",
    slug: "' . $slug . '",
    date_launch: "' . $record['Date launches'] . '",
    date_expire: "' . $record['Date expires'] . '",
    markup_skeleton: \'\n\
        <form id="' . $slug . '" action="http://denverpostplus.com/app/report-card/index.php" method="POST">\n\
            <h2></h2>\n\
            <p></p>\n\
            <div class="letter_grades">\n\
                <div><a href="#" class="letter" id="a" onClick="update_form(this);">A</a></div>\n\
                <div><a href="#" class="letter" id="b" onClick="update_form(this);">B</a></div>\n\
                <div><a href="#" class="letter" id="c" onClick="update_form(this);">C</a></div>\n\
                <div><a href="#" class="letter" id="d" onClick="update_form(this);">D</a></div>\n\
                <div><a href="#" class="letter" id="f" onClick="update_form(this);">F</a></div>\n\
            </div>\n\
\n\
            <input type="hidden" id="grade_input" name="grade_input" value="-1" />\n\
\n\
            <!-- For non-javascript-enabled browsers -->\n\
            <select id="grade_select" size="5">\n\
                <option value="-1" default></option>\n\
                <option value="4">A</option>\n\
                <option value="3">B</option>\n\
                <option value="2">C</option>\n\
                <option value="1">D</option>\n\
                <option value="0">F</option>\n\
            </select>\n\
            <script>jQuery("#grade_select").hide();</script>\n\
\n\
            <input type="image" src="images/default/grade-submit.gif" alt="Submit Your Grade">\n\
            <div id="result">\n\
                <div><a class="letter"></a></div>\n\
                <p></p>\n\
            </div>\n\
        </form>\n\
    \',
    init: function()
    {
        // HARD-CODED, for now ***
        $("#articleBody").append(this.markup_skeleton);
        $("#' . $slug . ' > h2").text(this.title);
        $("#' . $slug . ' > p").text(this.description);
    }
    };
window.onload = ' . $slug . '.init();
';
        $content .= "
function update_form(element)
{
    // Convert letter grade to numeric value
    var lookup = {
        a: 4,
        b: 3,
        c: 2,
        d: 1,
        f: 0
    };
    jQuery('#slug #grade_input').val(lookup[element.id]);
}

function lookup_letter_grade(avg)
{
    // Takes a float and returns a string letter-grade representation of that value.
    if ( avg > 3.8 ) return 'A';
    if ( avg > 3.5 ) return 'A-';
    if ( avg > 3.2 ) return 'B+';
    if ( avg > 2.8 ) return 'B';
    if ( avg > 2.5 ) return 'B-';
    if ( avg > 2.2 ) return 'C+';
    if ( avg > 1.8 ) return 'C';
    if ( avg > 1.5 ) return 'C-';
    if ( avg > .5 ) return 'D';
    return 'F';
}

$('#" . $slug . "').submit(function(e)
{
    var post_data = $(this).serializeArray();
    var formURL = $(this).attr('action');
    $.ajax(
    {
        url: formURL,
        type: 'POST',
        data: post_data,
        success:function(data, text_status, jqXHR) 
        {
            // The data returned from the server will be two values, separated
            // by a comma: the grade average, and the number of votes. 
            var values = data.split(',');
            grade_average = values[0];
            voters = values[1];
            var letter_grade = lookup_letter_grade(grade_average);
            $('#" . $slug . " #result a').text(letter_grade);
            $('#" . $slug . " #result p').text('With ' + voters + ' votes.');
            //console.log(data, text_status, jqXHR);
        },
        error: function(jqXHR, text_status, error_thrown) 
        {
            console.log(data, text_status, error_thrown);
        }
    });
    e.preventDefault(); // STOP default action
    e.unbind(); // unbind. to stop multiple form submit.
});";
 

        
        file_put_contents('_output/' . $slug, $content);
    endif;
endforeach;
