<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<xsl:template match="/products">
  <html>
    <head>
      <title>Reschevie — Product Catalog (XML View)</title>
      <style>
        body { font-family: 'Georgia', serif; background: #000; color: #F8F4EF; padding: 40px; }
        h1 { color: #C5A070; font-size: 36px; font-weight: 300; margin-bottom: 32px; }
        .product { border: 1px solid rgba(197,160,112,0.2); padding: 24px; margin-bottom: 16px; }
        .product-name { font-size: 22px; color: #C5A070; margin-bottom: 8px; }
        .product-detail { font-size: 13px; color: #888; margin-bottom: 4px; }
        .product-detail strong { color: #F8F4EF; }
        .badge { display: inline-block; padding: 3px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; }
        .available { background: rgba(100,200,100,0.1); color: #6CC; border: 1px solid rgba(100,200,200,0.2); }
        .sold { background: rgba(200,100,100,0.1); color: #C66; border: 1px solid rgba(200,100,100,0.2); }
      </style>
    </head>
    <body>
      <h1>Reschevie Product Catalog</h1>
      <p style="color:#888; margin-bottom:32px;">
        Total products: <strong style="color:#C5A070;"><xsl:value-of select="count(product)"/></strong>
      </p>
      <xsl:apply-templates select="product"/>
    </body>
  </html>
</xsl:template>

<xsl:template match="product">
  <div class="product">
    <div class="product-name"><xsl:value-of select="n"/></div>
    <div class="product-detail"><strong>Type:</strong> <xsl:value-of select="type"/></div>
    <div class="product-detail"><strong>Origin:</strong> <xsl:value-of select="origin"/></div>
    <div class="product-detail"><strong>Karat:</strong> <xsl:value-of select="karat"/></div>
    <div class="product-detail"><strong>Weight:</strong> <xsl:value-of select="weight"/></div>
    <div class="product-detail"><strong>Materials:</strong> <xsl:value-of select="materials"/></div>
    <div class="product-detail"><strong>Price:</strong>
      <xsl:choose>
        <xsl:when test="price = 'POA'"> Price Upon Request</xsl:when>
        <xsl:otherwise> ₱ <xsl:value-of select="price"/></xsl:otherwise>
      </xsl:choose>
    </div>
    <div class="product-detail" style="margin-top:8px;"><xsl:value-of select="description"/></div>
    <div style="margin-top:12px;">
      <span>
        <xsl:attribute name="class">badge <xsl:value-of select="status"/></xsl:attribute>
        <xsl:value-of select="status"/>
      </span>
      <xsl:if test="featured = 'true'">
        <span class="badge" style="background:rgba(197,160,112,0.1);color:#C5A070;border:1px solid rgba(197,160,112,0.2);margin-left:8px;">Featured</span>
      </xsl:if>
    </div>
  </div>
</xsl:template>

</xsl:stylesheet>
