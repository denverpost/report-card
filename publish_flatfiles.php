<?PHP
// Take the report card data and write HTML files suitable for publishing
// on a backup server for situations your CMS can't handle the javascript
// and form markup required to make this work.

// We're publishing two types of flatfiles: individual cards, and group cards.


$groups = array();
$template = file_get_contents('template.html');
$file_path = 'http://extras.denverpost.com/app/report-card/';

$i = 0;
if (($handle = fopen("records.csv", "r")) !== FALSE): 
    while (($csv = fgetcsv($handle)) !== FALSE):
        $i++;
        if ( $i == 1 ):
            $key = $csv;
            continue;
        endif;
        $record = array_combine($key, $csv);
        $body = '<script src="' . $file_path . 'cache/' . $record['slug'] . '.js"></script>';
        $content = str_replace('{{title}}', $record['Title'], $template);
        $content = str_replace('{{body}}', $body, $content);
        file_put_contents('_output/' . $record['slug'] . '.html', $content);
        if ( trim($record['Group slug']) != '' ):
            $groups[$record['Group slug']] .= $body;
        endif;
    
    endwhile;


    foreach ( $groups as $group_slug => $body ):
        $content = str_replace('{{title}}', '', $template);
        $content = str_replace('{{body}}', $body, $content);
        file_put_contents('_output/group/' . $group_slug . '.html', $content);
    endforeach;
endif;

