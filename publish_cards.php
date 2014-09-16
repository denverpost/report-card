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

// Command-line execution note:
// If the parameter 'filesonly' is passed, we rewrite the files
$filesonly = FALSE;
foreach ( $_SERVER['argv'] as $arg ):
    if ( $arg == 'filesonly' ):
        $filesonly = TRUE;
    endif;
endforeach;

date_default_timezone_set('America/Denver');
$connection = array(
    'user' => trim(file_get_contents('.mysql_user')),
    'password' => trim(file_get_contents('.mysql_pass')),
    'host' => trim(file_get_contents('.mysql_host')),
    'db' => 'reportcard');
$db = new mysqli($connection['host'], $connection['user'], $connection['password'], $connection['db']);
if ( $db->connect_errno )
{
    die('Could not connect to database: ' . $db->connect_error);
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

// We don't get str_getcsv() until php>5.3 (THANKS, PRODUCTION.)
//$csv = array_map('str_getcsv', file('records.csv'));
//$key = $csv[0];
//foreach ( $csv as $item ):
$i = 0;
if (($handle = fopen("records.csv", "r")) !== FALSE): 
while (($csv = fgetcsv($handle)) !== FALSE):
    $i++;
    if ( $i == 1 ):
        $key = $csv;
        continue;
    endif;
    $record = array_combine($key, $csv);

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

    // Check if the card exists in the database:
    $sql = 'SELECT id FROM cards WHERE slug = "' . $record['slug'] . '" LIMIT 1';
    $result = $db->query($sql);

    // If it exists we don't do anything. If it doesn't, we add it to the db
    // and write / ftp a javascript representation of this data to a production server.
    if ( mysqli_num_rows($result) == 0 || $filesonly == TRUE ):
        
        // *** We're not doing anything with these date fields, yet.
        $date_expire = '';
        $date_launch = '';
        if ( trim($record['Date expires']) != '' )
            $date_expire = strftime('%Y-%m-%d', strtotime($record['Date expires']));
        if ( trim($record['Date launches']) != '' )
            $date_launch = strftime('%Y-%m-%d', strtotime($record['Date launches']));


        // Validate slug input
        $group_slug = preg_replace("/[^-_a-z0-9 ]/", '', strtolower($record['Group slug']));
        $slug = preg_replace("/[^-_a-z0-9 ]/", '', strtolower($record['slug']));

        $sql = 'INSERT INTO cards (slug, group_slug, title, description, date_launch, date_expire, grade_average, grades) 
                VALUES
                ("' . $slug . '", "' . $group_slug . '", "' . $record['Title'] . '", "' . $record['Description'] . '", "' . $date_launch . '", "' . $date_expire . '", 0, 0)';
        if ( $filesonly == FALSE )
            $result = $db->query($sql);

        // Now we write the file.
        // We replace dashes with underscores.
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
                <div><a href="#" class="letter" id="a" onClick="' . $slug . 'update_form(this); return false;">A</a></div>\n\
                <div><a href="#" class="letter" id="b" onClick="' . $slug . 'update_form(this); return false;">B</a></div>\n\
                <div><a href="#" class="letter" id="c" onClick="' . $slug . 'update_form(this); return false;">C</a></div>\n\
                <div><a href="#" class="letter" id="d" onClick="' . $slug . 'update_form(this); return false;">D</a></div>\n\
                <div><a href="#" class="letter" id="f" onClick="' . $slug . 'update_form(this); return false;">F</a></div>\n\
            </div>\n\
\n\
            <input type="hidden" id="grade_input" name="grade_input" value="-1" />\n\
            <input type="hidden" id="slug" name="slug" value="' . $slug . '" />\n\
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
\n\
            <input class="submit" id="' . $slug . 'submit" type="image" src="http://extras.mnginteractive.com/live/media/site36/2014/0905/20140905_035618_grade-submit.gif" alt="Submit Your Grade">\n\
            <script>\n\
            jQuery("#' . $slug . ' #grade_select").hide();\n\
            jQuery("#' . $slug . 'submit").prop("disabled", true);\n\
            </script>\n\
            <div id="result">\n\
                <div><a class="letter"></a></div>\n\
                <p></p>\n\
            </div>\n\
        </form>\n\
    \',
    init: function()
    {
        if ( $("#articleBody").length ) $("#articleBody").append(this.markup_skeleton);
        else $("body").append(this.markup_skeleton);
        $("#' . $slug . ' > h2").text(this.title);
        $("#' . $slug . ' > p").text(this.description);
    }
    };
window.onload = ' . $slug . '.init();
';
        $content .= "
function " . $slug . "update_form(element)
{
    // Convert letter grade to numeric value
    var lookup = {
        a: 4,
        b: 3,
        c: 2,
        d: 1,
        f: 0
    };
    var letters = ['a', 'b', 'c', 'd', 'f'];
    var letter_count = 5;
    for ( i = 0; i < letter_count; i++ )
    {
        jQuery('#$slug .letter_grades #' + letters[i]).removeClass('letter_highlight');
    }

    jQuery('#$slug .letter_grades #' + element.id).addClass('letter_highlight');
    jQuery('#$slug #grade_input').val(lookup[element.id]);
    jQuery('#" . $slug . "submit').prop('disabled', false);
    return false;
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
            $('#" . $slug . " #result').show();
            $('#" . $slug . " .submit').hide();
            $('#" . $slug . " #result a').text(letter_grade);
            $('#" . $slug . " #result p').html('<strong>Readers have rated this a ' + letter_grade + ' on average, ' + voters + ' have voted.</strong>');
            //console.log(data, text_status, jqXHR);
        },
        error: function(jqXHR, text_status, error_thrown) 
        {
            console.log(data, text_status, error_thrown);
        }
    });
    e.preventDefault(); // STOP default action
    $.cookie('$slug', 1, { path: '/', expires: 999999 });
    //e.unbind(); // unbind. to stop multiple form submit.
    $(this).attr('action', '');
});";
 

        
        file_put_contents('_output/cache/' . $record['slug'] . '.js', $content);
    endif;
endwhile;
endif;
