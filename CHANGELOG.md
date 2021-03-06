# Modifications:

## Updated: 18. Aug. 2017

#### 18.08.2017
- Deposition Agreement DB is created
- Dep Agree Form: if you not finished your form then you can continue it later. For this please add your repoid to the url and the form will load your last saved data.
- Roots view pagination implemented

#### 11.08.2017
- Dissemination services added to the detail views

#### 10.08.2017
- ACL added
- Resource Detail views extended with ACL informations

#### 19.07.2017
- Redmine 8881 project

#### 13.07.2017
- Add form Autocomplete and AC title method changed
- New Sparql query added for the AutoComplete function

#### 04.07.2017
- bugfixes
- detail view error message extended

#### 14.06.2017
- sideBar Class List changes

#### 13.06.2017
- changes, because of the repo-php-util new version
- root resource delete function

#### 12.06.2017
- resource url changes in the table view.
- thumbnail added to the search result view
- custom error page to handle 3rd party libraries exceptions

#### 09.06.2017
- table view changed, now resource URL is hidden if the Resource title is available. Also they are clickable and the result will opens a new result table in the drupal GUI (not forwarding to the fedora anymore).

#### 08.06. 2017
- Deposition Form and pdf generatring added
- OEAW fonts added for the PDF

#### 11.05.2017
- version2 branch added because of the new vesion of the repo-php-util 

#### 05.05.2017
- Cardinality added to the New and Edit Forms. Now the form input fields will be generated based on the cardinality values.
- Cardinality types:
-- cardinality -> then 1 input field and it will be required
-- min-cardinality: 2 -> then 2 input field will be generated
-- max-cardinality: 3 -> then 1 input field will be generated and the user will have an add/remove button to add maximum 3 input fields.


#### 19.04.2017
- small bugfixes
- cardinality sql

#### 06.04.2017
- add and edit form bugfix
- sidebar class and search property listing in alphabetical order

#### 31.03.2017
- multiple identifier will be available now in the detail table view.

#### 30.03.2017
- resource delete function added to the detail views
- template bugfixes
- search bugfix - double values
- download icon bugfix

#### 27.03.2017
- New Resource success page created 
- Edit Resource success page created
- Local Drupal config values removed, now the modul using only the config.ini values.

#### 24.03.2017
- EasyRdf Graph Resources label output character coding changed.

#### 17.03.2017
- code refactoring
- detail table image bug fixed
- some small bugfix

#### 16.03.2017
- easyRDF namespace problem first beta check, and sidebar class changes

#### 14.03.2017
- easyRDF updated to the namespaces version and because of this we updated the drupal module too

#### 09.03.2017
- thumbnails extended to the foaf image rdf type too, not just for the thumbnails

#### 07.03.2017
- bugfixes on the search result views
- table property uris changed to prefixes
- lightbox2 added to the layout
- thumbnails now available in child tables, and in the detail views.
- thumbail images using lightbox2


#### 23.02.2017
- bugfixes
- thumbnail development started

#### 20.02.2017
- #8015 resolved

#### 17.02.2017
- sparql query changes
- php 7 optimizations


#### 09.02.2017
- there was a bug in the tablelisting, and if we had multiple elements for one property, then I am used the wrong EasyRdf method. Now i Fixed it.
- #7877 - solved. The list view contained the Fedora button, if it was a binary then after the click action the content was downloaded, otherwise the user was forwareded to the fedora page. 
 Now I am also extended the table views, and now every root resource which is a Binary Resource, has a download icon next to the title.
- Fedora and FedoraResources classes updated and because of this I started to implement the built in queries, to we can avoid the using of sparql queries on the drupal GUI. 

#### 07.02.2017
- Pdf upload allowed
- Datatable entries changed to 25 element/page

#### 06.02.2017
-  Basic Rest API added to the module (#7832)

#### 02.02.2017
- Doorkeeper will generate the identifier automatically (#7348)
- EditForm I added the readonly attribute to the identifier input fields
- AddForm, I removed the identifier input field from the form, because doorkeeper will generate it.


#### 31.01.2017
- EditForm bugfix

#### 26.01.2017
- sparql querys removing from the code and use fedora class(#7349) -> edit view done
- autocomplete old and new value text has some encoding problem, solved
- 

#### 25.01.2017
- Edit Form Autocomplete extended(#7693): new method which is increasing the autocomplete speed, the values has links now.
- Edit form title added
- some sparql query removed and i added fedora and fedora resource methods (oeaw_details, oeaw_search)
- Search Form improvements: Now the search form is not only searchin in the strings, if the searched property is a url, then the module will check the entered value in the title/name/fedoraIdProp
If there is a property whit this label then the module will get the acdh identifier for it, run the search again with the acdh identifier on the given property.

#### 19.01.2017
- Edit Form Autocomplete extended(#7693): the selected value from the Autocomplete will insert the uri to the input field. 
But the selected uri label/title/name will appear below the input field.


#### 17.01.2017
v.1.1 is done:
- shows the dc:title for the users if exists, if not then the "RAW URI"
- pass back the title and uri to the user frontend Form.
- now Autocomplete searches on the following property's:
    - common labels (skos:prefLabel, rdfs:label, foaf:name, dc:title)
    - resource URI's
    - acdhid -> which is "fedoraIdProp" from the drupal config.ini file


#### 16.01.2017
- Autocompletion version 1 added to the Edit Form (#7357)
- Autocompletion version 1 added to the Add new resource Form (#7357)

#### 13.01.2017
- Example AutoCompletion Form added (#7357): You can reach it by: http://yourdomain.com/oeaw_ac_form
- Sparql query changes based on ticket #7349 
- Drupal and Jquery conflicts solved
