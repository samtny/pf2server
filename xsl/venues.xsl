<?xml version="1.0" encoding="ISO-8859-1"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/pinfinderapp">
  <html>
  <head>
	<style type="text/css">
		body {
			background-color: #dedede;
		}
		
			body h3 {
				margin: 0px;
			}
		
		table.listView {
			border-collapse: collapse;
			width: 350px;
		}
		
		.venue {
			float: left;
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
			}
			
			.games table td.col1 {
				width: 250px;
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
  <h2>Venues</h2>
  <table border="1" class="listView">
    <xsl:for-each select="locations/loc">
		<tr>
		  <td>
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
			<div class="venue">
			
				<xsl:choose>
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
				</xsl:choose>
				
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
							<td>
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
		  </td>
		</tr>
    </xsl:for-each>
  </table>
  </body>
  </html>
</xsl:template>

</xsl:stylesheet> 