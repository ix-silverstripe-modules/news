<% include SideBar %>
<div class="content-container unit size3of4 lastUnit">
	<article>
		<h1>$Title</h1>
		<div class="content">
		
		
<% loop $ArchiveNews.GroupedBy(DateMonth) %>
	<h4>$DateMonth</h4>
	<ul>
	<% loop $Children %>
	    <li><a href="$Link">$Title ($Date.Nice)</a></li>
	<% end_loop %>
	</ul>
<% end_loop %>


		</div>
	</article>
</div>