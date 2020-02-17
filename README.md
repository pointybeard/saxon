# Saxon/C XSLT Processing Extension for Symphony CMS

-   Version: 0.1.0
-   Date: Feb 17 2020
-   [Release notes](https://github.com/pointybeard/saxon/blob/master/CHANGELOG.md)
-   [GitHub repository](https://github.com/pointybeard/saxon)

A [Symphony CMS](http://getsymphony.com) that adds Saxon/C (XSLT 3.0) processing capabilities.

### Requirements

This extension has the following requirements:

- PHP7.3 or later
- [Saxon-HE/C 1.1.2](http://www.saxonica.com/saxon-c/index.xml#archive)
- [Symphony CMS 2.7.10 Extended](https://github.com/pointybeard/symphony-2/tree/2.7.10-extended) or [Extended Essentials](https://github.com/pointybeard/symphony-2/tree/2.7.10-extended-essentials)

## Installation

Add it to the `/extensions` folder of your Symphony CMS installation, then enable it via "System" > "Extensions".

Once installed, choose `Saxon/C (XSLT 3.0)` as your XSLT processor in "System" > "Preferences".

### Installing Symphony CMS (Extended)

This is an extension for [Symphony CMS (Extended)](https://github.com/pointybeard/symphony-2/tree/2.7.10-extended). Use the following git commands to switch your Symphony 2.7.10 installation to 2.7.10 Extended.

```
git remote add pointybeard https://github.com/pointybeard/symphony-2.git
git fetch pointybeard
git checkout -b 2.7.10-extended pointybeard/2.7.10-extended
```

## Installing Saxon-HE/C

PHP does not offer XSLT 3.0 processing out of the box. In order to run this processor, you will need to install the Saxon-HE/C version of [Saxon](http://www.saxonica.com/). Follow the these steps to get Saxon-HE/C running on your server and available to PHP.

For this setup, we will assume Linux 64-bit (`libsaxon-HEC-setup64-v1.2.1.zip`) which you can download from <http://www.saxonica.com/download/c.xml>. Substitute the file name below for other systems and versions.

1. Run the following commands on your server to build and install the Saxon module for PHP:

```
cd /usr/local
wget -q "http://www.saxonica.com/saxon-c/libsaxon-HEC-setup64-v1.1.2.zip"
unzip "libsaxon-HEC-setup64-v1.1.2.zip"
./libsaxon-HEC-setup64-v1.1.2 -batch

ln -s "/usr/local/Saxonica/Saxon-HEC1.1.2/libsaxonhec.so" /usr/lib/libsaxonhec.so
ln -s "/usr/local/Saxonica/Saxon-HEC1.1.2/rt" /usr/lib/rt

bash -c "echo '# JetVM env path (required for Saxon)' > /etc/ld.so.conf.d/jetvm.conf"
bash -c "echo /usr/lib/rt/lib/amd64 >> /etc/ld.so.conf.d/jetvm.conf"
bash -c "echo /usr/lib/rt/lib/amd64/jetvm >> /etc/ld.so.conf.d/jetvm.conf"
ldconfig

cd "/usr/local/Saxonica/Saxon-HEC1.1.2/Saxon.C.API/"
/usr/bin/phpize
./configure --enable-saxon
make
make install

bash -c "echo env[LD_LIBRARY_PATH] = /usr/lib/rt/lib/amd64:/usr/lib/rt/lib/amd64/jetvm > /etc/php/7.4/apache2/conf.d/saxon_ld_library_path.conf"
bash -c "echo extension=saxon.so > /etc/php/7.4/apache2/conf.d/20-saxon.ini"

cd "/etc/php/7.4/apache2/conf.d/"
mv 20-saxon.ini "/etc/php/7.4/mods-available/"
mv "/etc/php/7.4/mods-available/20-saxon.ini" "/etc/php/7.4/mods-available/saxon.ini"
```

2. Enable Saxon with the following command:

`phpenmod saxon`

3. Check it is enabled and working correctly with the following:

`php -i | grep Saxon`
`php -r '$proc = new Saxon\SaxonProcessor(); var_dump($proc); echo $proc->version();'`

## Usage

The way page XSL is written is not different, however, developers get access to the newer features of XSLT (2.0 and onwards). You can read about some of the changes and benefits to using XSLT 3.0 here <https://www.xml.com/articles/2017/02/14/why-you-should-be-using-xslt-30/>.

### Current Limitations

Saxon/C has a few minor differences in how it compiles XSL which will impact how you construct your pages.

1. **Automatic Declaration of Parameters** -- Previously, all page parameters were avaialble automatically as xsl params. E.g. `$root`, and `$current-page` as well as in the XML itself (e.g. `/data/params/root`). There was no need to explicltly declare these via `xsl:param`. This has changed and any parameters being used that are provided by Symphony will need to be declared first (e.g. `<xsl:param name="root"/>`) otherwise you will get an error.

2. **Arrays of values in page parameters** -- Ordinarily, arrays of values (for example, Data source param output) was available in both the XML (i.e. `/data/params`) and as declared params (e.g. `$page-types`). Due to a bug in the Saxon/C binary for PHP, attempting to include such params automatically as a parameter causes Saxon to crash. As a result, any params that are arrays will ONLY be available via the XML.

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/saxon/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/saxon/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Saxon/C XSLT Processing Extension for Symphony CMS" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
