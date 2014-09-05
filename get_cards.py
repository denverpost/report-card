#!/usr/bin/env python
# Update the Report Card javascript files
import os
import csv
import gspread
from spready.filewrapper.FileWrapper import FileWrapper
from optparse import OptionParser
from spready.spready import Spready

if __name__ == '__main__':
    parser = OptionParser()
    parser.add_option("-v", "--verbose", dest="verbose", action="store_true", default=False)
    (options, args) = parser.parse_args()

    # The names of the worksheets within the spreadsheets we're dealing with 
    # should be passed as arguments to the script.
    # Ex:
    # >>> python get_cards.py

    key = FileWrapper('.googlekey')
    config = {
        'output_path': '%s/_output/' % os.path.dirname(os.path.realpath(__file__)),
        'sheet_name': 'report-card-master',
        'account': 'joe.murphy@gmail.com',
        'key': key.read().strip()
    }
    spready = Spready(**config)
    rows = spready.get_rows(options, 'responses')
    # So. The production server runs on CentOs 5, which is the root of evil.
    # Running a modern version of Python on CentOs 5 was doable, installing the
    # necessary dependencies was not.
    # We've got three things we need to do with the spreadsheet data:
    #    1. Check if the card is in the database
    #    2. If it's not, add the card to the db
    #    3. If it's not, write the card javascript file and FTP it to extras
    # So to do that, we're going to write the spreadsheet data to a flatfile,
    # ingest that flatfile with PHP and do all the database / FTP work with PHP.
    #
    # Sigh.

    fh = open('records.csv', 'w')
    writer = csv.writer(fh)
    for row in rows:
        writer.writerow(row)
    fh.close()
