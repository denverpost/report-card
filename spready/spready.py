#!/usr/bin/env python
# A wrapper for handling Google spreadsheet updates
# Note: You'll need a key to access the spreadsheet. You can create one here:
# https://accounts.google.com/b/0/IssuedAuthSubTokens?hide_authsub=1
import os
import sys
import json
import gspread
from filewrapper.FileWrapper import FileWrapper
from optparse import OptionParser
import doctest


class Spready:
    """ Handle publishing Google Spreadsheet data

        >>> spready = Spready('test.txt')
        >>> print spready.json
        {u'test': 1}
        """
    def __init__(self, *args, **kwargs):
        """ init expects a dict of keyword arguments looking something like:

            """
        if 'verbose' in kwargs:
            self.verbose = True
        self.output_path = kwargs['output_path']
        self.sheet_name = kwargs['sheet_name']
        google = {
            'key': kwargs['key'],
            'account': kwargs['account']
        }
        self.spread = gspread.login(google['account'], google['key'])

    def set_sheet_name(self, value):
        """ Set the object's sheet_name var.
            >>> spready = Spready('test.txt')
            >>> sheet_name = spready.set_sheet_name('worksheet-master')
            >>> print sheet_name
            worksheet-master
            """
        self.sheet_name = value
        return self.sheet_name

    def set_worksheet(self, value):
        """ Set the object's worksheet var.
            >>> spready = Spready('test.txt')
            >>> worksheet = spready.set_worksheet('worksheet-master')
            >>> print worksheet
            worksheet-master
            """
        self.worksheet = value
        return self.worksheet

    def slugify(self, slug):
        return slug.lower().replace(' ', '-')

    def update_sheet(self, worksheet, options):
        """ Loop through a sheet and perform a certain action on a certain
            field in each record. Until this needs to be more, it will be
            hard-coded.
            *** Too hard-coded to be useful, currently.
            """
        sheet = self.spread.open(self.sheet_name).worksheet('popular')
        rows = sheet.get_all_values()
        keys = rows[0]
        i = 0
        for row in rows:
            i = i + 1
            if i == 1:
                # Get a list of the fields we have available in the spreadsheet
                # for use when we're actually updating the spreadsheet
                keys = row
                continue
            col = 5
            slug = self.slugify(row[0])
            # First update the slug
            sheet.update_cell(i, col, slug)
            
    def publish(self, options, worksheet=None):
        """ Write the worksheet data as a json object that can be 
            included on the site.
            """
        if worksheet == None:
            worksheet = self.worksheet
        sheet = self.spread.open(self.sheet_name).worksheet(worksheet)
        rows = sheet.get_all_values()
        keys = rows[0]
        i = 0 
        lines = []
        fn= open('%s%s.json' % (self.output_path, worksheet), 'w');
        fn.write('{')
        for row in rows:
            i += 1
            if i == 1:
                continue
            record = dict(zip(keys, row))
            if 'slug' in record:
                if record['slug'] == '':
                    record['slug'] = 'record%d' % i
                line = '    "%s": %s,' % (record['slug'], json.dumps(record, ensure_ascii=True))
            else:
                line = ' %s,' % (json.dumps(record, ensure_ascii=True))
            lines += [line]

        # Kill the trailing comma, if any.
        lastrow = len(lines) -1
        if lines[lastrow][-1:] == ',':
            lines[lastrow] = lines[lastrow][:-1]
        fn.write('\n'.join(lines))
        fn.write('}')
        fn.close
    
if __name__ == '__main__':
    parser = OptionParser()
    parser.add_option("-a", "--action", dest="action", default="update")
    parser.add_option("-v", "--verbose", dest="verbose", action="store_true", default=False)
    (options, args) = parser.parse_args()

    doctest.testmod(verbose=options.verbose)

    # The names of the worksheets within the spreadsheets we're dealing with 
    # should be passed as arguments to the script.
    # Ex:
    # >>> ./filename.py -a publish dispensary city strain

    spready = Spready()
    for arg in args:
        if options.action == 'update_sheet':
            spready.update_sheet(arg, options)
        if options.action == 'publish':
            spready.publish(arg, options)
