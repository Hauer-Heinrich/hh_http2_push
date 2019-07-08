# hh_http2_push
hh_http2_push is a TYPO3 extension.
Implements http2 server push - [descriptionLink](https://www.smashingmagazine.com/2017/04/guide-http2-server-push/ http2-push description").
Needs a boolean cookie - if cookie=true sets the additional link (push) response header.

### Installation
... like any other TYPO3 extension e. g. [extensions.typo3.org](https://extensions.typo3.org/extension/hh_slider/ "TYPO3 Extension Repository")

### To use it
Add to your TypoScript for example this:
page {
    includeCSS {
        file10 = yourPath.css
        file10.preloadPush = 1
    }
}

### Features
- works for compressed files
- works for concatenate files
- works for "normal" files

### Todos
- optimize code

### Deprecated

### IMPORTENT NOTICE

#### Extension configuration
![example picture from backend](github/images/extconfig.jpg?raw=true "extconfig")
#### Browser developer view
Open browser dev-tools e. g. chrome, go to tab network and check Initiator field = 'Push / Other'.
(on localhost without https etc. Initiator field = 'Other')
![example picture from the browser dev-tools](github/images/browserview.jpg?raw=true "browserview")

##### Copyright notice

This repository is part of the TYPO3 project. The TYPO3 project is
free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

The GNU General Public License can be found at
http://www.gnu.org/copyleft/gpl.html.

This repository is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

This copyright notice MUST APPEAR in all copies of the repository!

##### License
----
GNU GENERAL PUBLIC LICENSE Version 3
