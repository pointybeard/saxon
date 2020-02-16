# Saxon/C XSLT Processing Extension for Symphony CMS

-   Version: 0.1.0
-   Date: Feb 15 2020
-   [Release notes](https://github.com/pointybeard/saxon/blob/master/CHANGELOG.md)
-   [GitHub repository](https://github.com/pointybeard/saxon)

A [Symphony CMS](http://getsymphony.com) that adds Saxon/C (XSLT 3.0) processing capabilities.

### Requirements

This extension has the following requirements:

- PHP7.3 or later
- [Saxon-HE/C 1.1.2](http://www.saxonica.com/saxon-c/index.xml#archive)
- [Symphony CMS 2.7.10 Extended](https://github.com/pointybeard/symphony-2/tree/2.7.10-extended) or [Extended Essentials](https://github.com/pointybeard/symphony-2/tree/2.7.10-extended-essentials)

**See below for specific instructions on how to install Symphony CMS (Extended) and Saxon-HE/C.**

## Installation

Add it to the `/extensions` folder of your Symphony CMS installation, then enable it though the interface.

## Usage



### Installing Symphony CMS (Extended)
This is an extension for [Symphony CMS (Extended)](https://github.com/pointybeard/symphony-2/tree/2.7.10-extended). Use the following git commands to switch you Symphony 2.7.10 installation to 2.7.10 Extended.

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

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/saxon/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/saxon/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Saxon/C XSLT Processing Extension for Symphony CMS" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
