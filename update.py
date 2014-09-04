#!/usr/bin/env python
# Update the Report Card javascript files
import os
import sys
import json
import gspread
from spready.filewrapper.FileWrapper import FileWrapper
from optparse import OptionParser
from spready.spready import Spready
import doctest

if __name__ == '__main__':
    parser = OptionParser()
    parser.add_option("-v", "--verbose", dest="verbose", action="store_true", default=False)
    (options, args) = parser.parse_args()

    # The names of the worksheets within the spreadsheets we're dealing with 
    # should be passed as arguments to the script.
    # Ex:
    # >>> ./update.py

    key = FileWrapper('.googlekey')
    config = {
        'output_path': '%s/_output/' % os.path.dirname(os.path.realpath(__file__)),
        'sheet_name': 'report-card-master',
        'account': 'joe.murphy@gmail.com',
        'key': key.read().strip()
    }
    spready = Spready(**config)
    spready.publish(options, 'responses')
