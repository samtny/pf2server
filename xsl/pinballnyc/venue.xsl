<?xml version="1.0" encoding="ISO-8859-1"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:variable name="img_venue_certified">
	<img src="http://www.pinballnyc.com/wp-content/themes/pinballnyc/images/leaguecertified2.gif" alt="League Certified" />
</xsl:variable>

<xsl:variable name="img_venue_claimed">
	<img src="http://www.pinballnyc.com/wp-content/themes/pinballnyc/images/leaguetaken2.gif" alt="League Certified (Claimed)" />
</xsl:variable>

<xsl:template match="/pinfinderapp">
  <html>
  <head>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=AIzaSyCMWL8VtaTA5ORZro3vPvwfZxWel1sgwPg&amp;sensor=false"></script>
	<style type="text/css">
		body {
			background-color: #ffffff;
			font-family: 'TeXGyreAdventor', Century Gothic, 'Lucida Sans Unicode', 'Lucida Grande', Arial, Helvetica, sans-serif;
		}
			
			body h1 {
				color: #BE1E2D;
			}
			
			body h2 {
				color: #BE1E2D;
				font-size: 20px;
			}
			
			body h3 {
				font-size: 16px;
				margin: 0px;
			}
			
			
		
		a {
			color: inherit;
		}
		
		table.listView {
			border-collapse: collapse;
			width: 350px;
		}
		
		.venueBox {
			margin-top: 10px;
			margin-bottom: 10px;
			padding-bottom: 10px;
			border-bottom: 1px solid #cccccc;
		}
		
		.venue {
			width: 100%;
		}
		
			.venue .badge {
				float: right;
			}
			
			.venue ul {
				list-style: none;
				padding-left: 0px;
			}
		
		.photo img {
			float: left;
			height: 60px;
			width: 90px;
			border: 3px solid #000000;
			margin: 0 15px 0 0;
		}
		
		.slide img {
			height: 250px;
			width: 374px;
			border: 3px solid #000000;
			margin: 0 15px 0 0;
		}
		
		.address ul {
			list-style: none;
			padding-left: 0px;
		}
		
		.games {
			clear: left;
		}
		
			.games table {
				border-collapse: collapse;
				width: 374px;
				font-size: 14px;
			}
			
			.games table td.col1 {
				width: 150px;
			}
			
			.games table td.col2 {
				width: 100px;
			}
			
			.games table td.col3 {
				text-align: right;
			}
		
		.mapContainer {
			margin-top: 16px;
		}
		
		.left {
			float: left;
		}
		
		.right {
			float: right;
		}
		
	</style>
  </head>
  <body>

	<xsl:for-each select="locations/loc[count(. | key('locations-by-neighborhood', neighborhood)[1]) = 1]">
		
		<div class="venue">
		
			<h1><xsl:value-of select="name" /></h1>
			<ul>
				<li><xsl:value-of select="addr" /></li>
				<li><xsl:value-of select="city" />, <xsl:value-of select="state" /><xsl:text> </xsl:text><xsl:value-of select="zipcode" /></li>
				<li><xsl:value-of select="phone" /></li>
				<xsl:choose>
					<xsl:when test="url">
						<li>
							<a target="_blank">
								<xsl:variable name="url" select="url" />
								<xsl:attribute name="href">
									<xsl:if test="not(contains($url, 'http://'))">
										http://
									</xsl:if>
									<xsl:value-of select="$url" />
								</xsl:attribute>
								<xsl:if test="not(contains($url, 'http://'))">
									http://
								</xsl:if>
								<xsl:value-of select="$url"/>
							</a>
						</li>
					</xsl:when>
				</xsl:choose>
			</ul>
			
			<div class="slideshow">
				<xsl:for-each select="images/image">
					<div class="slide">
						<img>
							<xsl:attribute name="src"><xsl:value-of select="@url" /></xsl:attribute>
						</img>
					</div>
				</xsl:for-each>
			</div>
			
			<h2>The Machines</h2>
			
			<div class="games">
				<table>
					<xsl:for-each select="game">
						<tr>
							<td class="col1">
								<a>
									<xsl:attribute name="href">http://ipdb.org/machine.cgi?<xsl:value-of select="ipdb" /></xsl:attribute>
									<xsl:attribute name="target">_blank</xsl:attribute>
									<xsl:value-of select="fullname"/>
								</a>
							</td>
							<td class="col2">
								<xsl:choose>
									<xsl:when test="year">
										<xsl:choose>
											<xsl:when test="manufacturer">
												<xsl:value-of select="year"/>, <xsl:value-of select="manufacturer"/>
											</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="year"/>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:when>
								</xsl:choose>
							</td>
							<td class="col3">
								<xsl:choose>
									<xsl:when test="cond = 0 or cond = 1 or cond = 2">
										<img src="http://www.pinballnyc.com/wp-content/themes/pinballnyc/images/1-ball.gif" />
									</xsl:when>
									<xsl:when test="cond = 3">
										<img src="http://www.pinballnyc.com/wp-content/themes/pinballnyc/images/3-ball.gif" />
									</xsl:when>
									<xsl:when test="cond = 4 or cond = 5">
										<img src="http://www.pinballnyc.com/wp-content/themes/pinballnyc/images/4-ball.gif" />
									</xsl:when>
								</xsl:choose>
							</td>
						</tr>
					</xsl:for-each>
				</table>
			</div>
			
			<div class="mapContainer">
				<div id="map" style="width: 374px; height: 350px; position: relative; background-color: rgb(229, 227, 223); overflow: hidden;" />
				<script type="text/javascript">
					var latlng = new google.maps.LatLng(<xsl:value-of select="lat" />, <xsl:value-of select="lon" />);
					var myOptions = {
						zoom: 16,
						center: latlng,
						scrollwheel: true,
						scaleControl: false,
						disableDefaultUI: false,
						mapTypeId: google.maps.MapTypeId.ROADMAP
					};
					var map = new google.maps.Map(document.getElementById("map"), myOptions);
					var marker = new google.maps.Marker({
						map: map,
						position: map.getCenter()
					});
				</script>
			</div>
			
		</div>
		
	</xsl:for-each>
	  
  </body>
  </html>
</xsl:template>

</xsl:stylesheet>