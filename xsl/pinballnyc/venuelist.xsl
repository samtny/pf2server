<?xml version="1.0" encoding="ISO-8859-1"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:variable name="img_venue_certified">
	<img src="http://www.pinballnyc.com/wp-content/themes/pinballnyc/images/leaguecertified2.gif" alt="League Certified" />
</xsl:variable>

<xsl:variable name="img_venue_claimed">
	<img src="http://www.pinballnyc.com/wp-content/themes/pinballnyc/images/leaguetaken2.gif" alt="League Certified (Claimed)" />
</xsl:variable>

<xsl:key name="locations-by-neighborhood" match="loc" use="neighborhood" />

<xsl:template match="/pinfinderapp">
  <html>
  <head>
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
		
		.photo img {
			float: left;
			height: 60px;
			width: 90px;
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
				width: 100%;
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
		
		.left {
			float: left;
		}
		
		.right {
			float: right;
		}
		
	</style>
  </head>
  <body>
  <h1>the venues - <xsl:value-of select="meta/q" /></h1>
	
	<xsl:for-each select="locations/loc[count(. | key('locations-by-neighborhood', neighborhood)[1]) = 1]">
		<xsl:sort select="neighborhood" />
		<xsl:choose>
			<xsl:when test="neighborhood != ''">
				<a><xsl:attribute name="href">#<xsl:value-of select="neighborhood" /></xsl:attribute><xsl:value-of select="neighborhood" /></a>
				<br />
			</xsl:when>
		</xsl:choose>
	</xsl:for-each>
	
	<xsl:for-each select="locations/loc[count(. | key('locations-by-neighborhood', neighborhood)[1]) = 1]">
		<xsl:sort select="neighborhood" />
		
		<xsl:choose>
			<xsl:when test="neighborhood != ''">
				<h2><a><xsl:attribute name="name"><xsl:value-of select="neighborhood" /></xsl:attribute><xsl:value-of select="neighborhood" /></a></h2>
			</xsl:when>
		</xsl:choose>
		
		<table class="listView">
			<xsl:for-each select="key('locations-by-neighborhood', neighborhood)">
				<xsl:sort select="name" />
				
				<tr>
				  <td>
					<div class="venueBox">
						<div class="venue">
							
							<xsl:for-each select="images/image">
								<xsl:choose>
									<xsl:when test="@default=1">
										<div class="photo">
											<img>
												<xsl:attribute name="src"><xsl:value-of select="@url" /></xsl:attribute>
											</img>
										</div>
									</xsl:when>
								</xsl:choose>
							</xsl:for-each>
							
							<div class="badge">
								<xsl:for-each select="leagues/league">
									<xsl:choose>
										<xsl:when test="@key=10000">
											<xsl:choose>
												<xsl:when test="teams">
													<xsl:copy-of select="$img_venue_claimed" />
												</xsl:when>
												<xsl:otherwise>
													<xsl:copy-of select="$img_venue_certified" />
												</xsl:otherwise>
											</xsl:choose>
										</xsl:when>
									</xsl:choose>
								</xsl:for-each>
							</div>
							
							<!--<xsl:choose>
								<xsl:when test="url">
									<a>
										<xsl:variable name="url" select="url" />
										<xsl:attribute name="href">
											<xsl:if test="not(contains($url, 'http://'))">
												http://
											</xsl:if>
											<xsl:value-of select="$url" />
										</xsl:attribute>
										<h3><xsl:value-of select="name"/></h3>
									</a>						
								</xsl:when>
								<xsl:otherwise>
									<h3><xsl:value-of select="name"/></h3>
								</xsl:otherwise>
							</xsl:choose>-->
							<a>
								<xsl:attribute name="href">
									http://pinballfinder.org/pf2/xsl/pinballnyc/venue.php?q=<xsl:value-of select="@key" />
								</xsl:attribute>
								<h3><xsl:value-of select="name"/></h3>
							</a>
							
							<div class="address">
								<ul>
									<li><xsl:value-of select="addr"/></li>
									<li><xsl:value-of select="city"/>, <xsl:value-of select="state"/> (<xsl:value-of select="zipcode"/>)</li>
								</ul>
							</div>
							
						</div>
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
					  </div>
					</td>
				</tr>
				
			</xsl:for-each>
		
		</table>
		
	</xsl:for-each>
	  
  </body>
  </html>
</xsl:template>

</xsl:stylesheet> 