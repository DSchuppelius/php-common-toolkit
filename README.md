# php-common-toolkit
Toolkit for common codes in my projects

## Requirements
The following tools are required to successfully run `dschuppelius/php-common-toolkit`:

### 1. TIFF Tools
Required for processing and handling TIFF files.
- **Windows**: [GnuWin32 TIFF Tools](https://gnuwin32.sourceforge.net/packages/tiff.htm)
- **Debian/Ubuntu**: 
  ```bash
  apt install libtiff-tools
  ```

### 2. Xpdf
Required for handling PDF files.
- **Windows**: [Xpdf Download](https://www.xpdfreader.com/download.html)
- **Debian/Ubuntu**:
  ```bash
  apt install xpdf
  ```

### 3. ImageMagick
For converting and processing image files.
- **Windows**: [ImageMagick Installer](https://imagemagick.org/archive/binaries/ImageMagick-7.1.1-39-Q16-HDRI-x64-dll.exe)
- **Debian/Ubuntu**:
  ```bash
  apt install imagemagick-6.q16hdri
  ```

### 4. muPDF Tools
For processing PDF and XPS documents.
- **Debian/Ubuntu**:
  ```bash
  apt install mupdf-tools
  ```

### 5. QPDF
For advanced PDF manipulation and processing.
- **Windows**: [QPDF Download](https://github.com/qpdf/qpdf/releases)
- **Debian/Ubuntu**:
  ```bash
  apt install qpdf
  ```

### Install the Toolkit into your Project

The Toolkit requires a PHP version of 8.1 or higher. The recommended way to install the SDK is through [Composer](http://getcomposer.org).

```bash
composer require dschuppelius/php-common-toolkit
```