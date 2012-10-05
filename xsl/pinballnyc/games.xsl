<?xml version="1.0" encoding="ISO-8859-1"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:key name="kAbbr" match="game" use="abbr" />

<xsl:template match="/">
	<xsl:apply-templates select="pinfinderapp/locations" />
</xsl:template>

<xsl:template match="locations">
	<ol>
	  <xsl:apply-templates mode="area-group" select="
		loc[
		  generate-id()
		  =
		  generate-id(
			key('kAbbr', abbr)[1]
		  )
		]
	  ">
		<xsl:sort select="abbr" data-type="text" />
		meh
	  </xsl:apply-templates>
	</ol>
</xsl:template>

<xsl:template match="loc" mode="area-group">
	<li>
	  <xsl:value-of select="concat('abbr ', abbr)" />
	</li>
</xsl:template>

</xsl:stylesheet> 