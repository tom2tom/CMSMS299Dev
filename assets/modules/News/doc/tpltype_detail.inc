<style type="text/css">
	ul.helptext {
	  list-style-type: disc;
	  margin-left: 1em;
	  margin-bottom: 1em;
	}
</style>

<h2>News module entry object reference:</h2>

<p>The News module's detail view exports the following variables:</p>
<ul class="helptext">
  <li>$entry - A simple object that contains information about the one entry.</li>
  <li>$category_name - (string) the category name for the category that this article belongs to.</li>
  <li>$category_link - (string) (deprecated) A link to a summary view for articles in this category.</li>
</ul>

<h3>$entry Object Reference</h3>
<ul class="helptext">
  <li>id <em>(integer)</em> - The unique article id.</li>
  <li>author_id <em>(integer)</em> - The userid of the article creator.</li>
  <li>title <em>(string)</em> - The title of the article.</li>
  <li>summary (text) - The summary text (may be empty or unset).</li>
  <li>extra (string)         = The "extra" data associated with the article (may be empty or unset).</li>
  <li>news_url (string)      = The url segment associated with this article (may be empty or unset).</li>
  <li>startdate (string)     = A string representing the date the article should begin to appear.  (may be empty or unset).</li>
  <li>enddate (string)       = A string representing the date the article should stop appearing on the site (may be empty or unset).</li>
  <li>category_id (integer)  = The unique id of the hierarchy level where this article resides (may be empty or unset)</li>
  <li>status (string)        = either 'draft' or 'published' indicating the status of this article.</li>
  <li>author (string)        = The username of the article creator.</li>
  <li>authorname (string)    = The full name of the article creator. Only applicable if that information exists in the admin data.</li>
  <li>category (string)      = The name of the category that this article is associated with.</li>
  <li>canonical (string)     = A full URL (prettified) to this articles detail view using defaults if necessary.</li>
  <li>useexp (integer)       = A flag indicating whether this article is using the expiry information.</li>
  <li>file_location (string) = A url containing the location where files attached the article are stored... the field value should be appended to this url.</li>
</ul>

<p>Members can be displayed by the following syntax: {$entry->membername} or assigned to another smarty variable using {assign var='foo' value=$entry->membername}.</p>
<p>The following members are available in the entry array:<br /></p>

<h3>field Object Reference</h3>
<p>The News\Field object contains data about the fields and their values that are associated with a particular news article.</p>
<ul class="helptext">
  <li>id (integer)  = The id of the field definition</li>
  <li>name (string) = The name of the field</li>
  <li>type (string) = The type of field</li>
  <li>max_length (integer) = The maximum length of the field (applicable only to text fields)</li>
  <li>item_order (integer) = The order of the field</li>
  <li>public (integer) = A flag indicating whether the field is public or not</li>
  <li>value (mixed)    = The value of the field.</li>
</ul>
