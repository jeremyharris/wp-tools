# WP Tools

WP Tools is a collection of shell commands for maintaining WordPress databases.

## Usage

To run the shell, simply run `wp-tools` from the command line and pass the
location of your WordPress installation as the `-w` argument along with a
command:

    $ wp-tools <command> -w /path/to/wordpress

## Commands

- `help`: Display help
- `move`: Move WP database entries to a new domain
- `clean-orphans`: Remove old, unused metadata

### `move`

WordPress hardcodes all of its urls, making it difficult to move databases 
between environments. This command helps alleviate that pain by performing all
of the repetitive database tasks for you.

### `clean-orphans`

Often times you can end up with extra cruft in your `postmeta` table. This
command will run through and remove any post meta that doesn't belong to an
existing post anymore.

## License

Copyright (c) 2012 Jeremy Harris

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

