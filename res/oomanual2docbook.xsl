<?xml version="1.0" encoding="UTF-8"?>

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	
	Copyright notice
	
	(c) 2005 Robert Lemke (robert@typo3.org)
	All rights reserved
	
	This stylesheet is part of the TYPO3 project. The TYPO3 project is
	free software; you can redistribute it and/or modify it under the 
	terms of the GNU General Public License as published by	the Free 
	Software Foundation; either version 2 of the License, or (at your 
	option) any later version.
	
	The GNU General Public License can be found at
	http://www.gnu.org/copyleft/gpl.html.
	
	This stylesheet is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	This copyright notice MUST APPEAR in all copies of the stylesheet!

	~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	
	Stylesheet for transforming typical TYPO3 manuals from the Open
	Office Writer 1.x format to DocBook 4.3
	
	Inspired by the official OpenOffice XSLT sheets.
	
	~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

<xsl:stylesheet version="1.0" 
	xmlns:style="http://openoffice.org/2000/style" 
	xmlns:text="http://openoffice.org/2000/text" 
	xmlns:office="http://openoffice.org/2000/office" 
	xmlns:table="http://openoffice.org/2000/table" 
	xmlns:draw="http://openoffice.org/2000/drawing" 
	xmlns:fo="http://www.w3.org/1999/XSL/Format" 
	xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns:dc="http://purl.org/dc/elements/1.1/" 
	xmlns:meta="http://openoffice.org/2000/meta" 
	xmlns:number="http://openoffice.org/2000/datastyle" 
	xmlns:svg="http://www.w3.org/2000/svg" 
	xmlns:chart="http://openoffice.org/2000/chart" 
	xmlns:dr3d="http://openoffice.org/2000/dr3d" 
	xmlns:math="http://www.w3.org/1998/Math/MathML" 
	xmlns:form="http://openoffice.org/2000/form" 
	xmlns:script="http://openoffice.org/2000/script" 
	xmlns:config="http://openoffice.org/2001/config" 
	office:class="text" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	exclude-result-prefixes="office meta  table number dc fo xlink chart math script xsl draw svg dr3d form config text style">

	<xsl:key name='headchildren' match="text:p | text:alphabetical-index | table:table | text:span | text:ordered-list | office:annotation | text:unordered-list | text:footnote | text:a | text:list-item | draw:plugin | draw:text-box | text:footnote-body | text:section" use="generate-id((..|preceding-sibling::text:h[@text:level='1']|preceding-sibling::text:h[@text:level='2']|preceding-sibling::text:h[@text:level='3']|preceding-sibling::text:h[@text:level='4']|preceding-sibling::text:h[@text:level='5'])[last()])"/>
	<xsl:key name="children" match="text:h[@text:level='2']" use="generate-id(preceding-sibling::text:h[@text:level='1'][1])"/>
	<xsl:key name="children" match="text:h[@text:level='3']" use="generate-id(preceding-sibling::text:h[@text:level='2' or @text:level='1'][1])"/>
	<xsl:key name="children" match="text:h[@text:level='4']" use="generate-id(preceding-sibling::text:h[@text:level='3' or @text:level='2' or @text:level='1'][1])"/>
	<xsl:key name="children" match="text:h[@text:level='5']" use="generate-id(preceding-sibling::text:h[@text:level='4' or @text:level='3' or @text:level='2' or @text:level='1'][1])"/>
	<xsl:key name="secondary_children" match="text:p[@text:style-name = 'Index 2']" use="generate-id(preceding-sibling::text:p[@text:style-name = 'Index 1'][1])"/>
    
	<xsl:template match="/office:document-content">
		<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE book PUBLIC "-//OASIS//DTD DocBook XML V4.3//EN" "http://www.oasis-open.org/docbook/xml/4.3/docbookx.dtd"&gt;</xsl:text>
			<book>
				<xsl:apply-templates/>
			</book>
	</xsl:template>

    <xsl:template match="text:h[@text:level='1']">
        <xsl:element name="chapter">
            <xsl:call-template name="id.attribute"/>
            <title>
                <xsl:apply-templates/>
            </title>
            <xsl:apply-templates select="key('headchildren', generate-id())"/>
            <xsl:apply-templates select="key('children', generate-id())"/>		
        </xsl:element>
    </xsl:template>

    <xsl:template match="text:h[@text:level='2'] | text:h[@text:level='3']| text:h[@text:level='4'] | text:h[@text:level='5']">
        <xsl:element name="section">
            <xsl:call-template name="id.attribute"/>
            <title>
                <xsl:apply-templates/>
            </title>
            <xsl:apply-templates select="key('headchildren', generate-id())"/>
            <xsl:apply-templates select="key('children', generate-id())"/>		
        </xsl:element>
    </xsl:template>

    <xsl:template match="text:p">
        <xsl:element name="para">
            <xsl:if test="child::text:reference-mark-start">
                <xsl:value-of select="."/>
            </xsl:if>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:template>

    <xsl:template match="text:ordered-list">
        <orderedlist>
            <xsl:apply-templates/>
        </orderedlist>
    </xsl:template>

    <xsl:template match="dc:language"/>
    
    <xsl:template match="office:body">
        <xsl:apply-templates select="key('headchildren', generate-id())"/>
        <xsl:apply-templates select="text:h[@text:level='1']"/>
    </xsl:template>

    <xsl:template match="table:table">
		<informaltable frame="all">
		    <xsl:call-template name="generictable"/>
		</informaltable>
    </xsl:template>
    
    
    <xsl:template name="generictable">
        <xsl:variable name="cells" select="count(descendant::table:table-cell)"/>
        <xsl:variable name="rows">
            <xsl:value-of select="count(descendant::table:table-row) "/>
        </xsl:variable>
        <xsl:variable name="cols">
            <xsl:value-of select="$cells div $rows"/>
        </xsl:variable>
        <xsl:variable name="numcols">
            <xsl:choose>
                <xsl:when test="child::table:table-column/@table:number-columns-repeated">
                    <xsl:value-of select="number(table:table-column/@table:number-columns-repeated+1)"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$cols"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:element name="tgroup">
            <xsl:attribute name="cols">
                <xsl:value-of select="$numcols"/>
            </xsl:attribute>
            <xsl:call-template name="colspec">
                <xsl:with-param name="left" select="1"/>
            </xsl:call-template>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:template>
    
    
    <xsl:template name="colspec">
        <xsl:param name="left"/>
        <xsl:if test="number($left &lt; ( table:table-column/@table:number-columns-repeated +2)  )">
            <xsl:element name="colspec">
                <xsl:attribute name="colnum">
                    <xsl:value-of select="$left"/>
                </xsl:attribute>
                <xsl:attribute name="colname">c
                    <xsl:value-of select="$left"/>
                </xsl:attribute>
            </xsl:element>
            <xsl:call-template name="colspec">
                <xsl:with-param name="left" select="$left+1"/>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>
    
    
    <xsl:template match="table:table-column">
        <xsl:apply-templates/>
    </xsl:template>
    
    
    <xsl:template match="table:table-header-rows">
        <thead>
            <xsl:apply-templates/>
        </thead>
    </xsl:template>
    
    
    <xsl:template match="table:table-header-rows/table:table-row">
        <row>
            <xsl:apply-templates/>
        </row>
    </xsl:template>
    
    
    <xsl:template match="table:table/table:table-row">
        <xsl:if test="not(preceding-sibling::table:table-row)">
            <xsl:text disable-output-escaping="yes">&lt;tbody&gt;</xsl:text>
        </xsl:if>
        <row>
            <xsl:apply-templates/>
        </row>
        <xsl:if test="not(following-sibling::table:table-row)">
            <xsl:text disable-output-escaping="yes">&lt;/tbody&gt;</xsl:text>
        </xsl:if>
    </xsl:template>
    
    
    <xsl:template match="table:table-cell">
        <xsl:element name="entry">
            <xsl:if test="@table:number-columns-spanned &gt;'1'">
                <xsl:attribute name="namest">
                    <xsl:value-of select="concat('c',count(preceding-sibling::table:table-cell[not(@table:number-columns-spanned)]) +sum(preceding-sibling::table:table-cell/@table:number-columns-spanned)+1)"/>
                </xsl:attribute>
                <xsl:attribute name="nameend">
                    <xsl:value-of select="concat('c',count(preceding-sibling::table:table-cell[not(@table:number-columns-spanned)]) +sum(preceding-sibling::table:table-cell/@table:number-columns-spanned)+ @table:number-columns-spanned)"/>
                </xsl:attribute>
            </xsl:if>
            <xsl:apply-templates/>
        </xsl:element>
    </xsl:template>
    
    
    <xsl:template match="text:p">
        <xsl:choose>
            <xsl:when test="@text:style-name='Table'"/>
            <xsl:when test="@text:style-name='Preformatted Text'">
				<xsl:element name="programlisting">
					<xsl:apply-templates/>
				</xsl:element>
            </xsl:when>
            <xsl:otherwise>
                <xsl:if test="not( child::text:span[@text:style-name = 'XrefLabel'] )">
                    <para>
                        <xsl:call-template name="id.attribute"/>
                        <xsl:apply-templates/>
                    </para>
                </xsl:if>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    
    <xsl:template match="text:unordered-list">
        <xsl:choose>
            <xsl:when test="@text:style-name='Var List'">
                <variablelist>
                    <xsl:apply-templates/>
                </variablelist>
            </xsl:when>
            <xsl:when test="@text:style-name='UnOrdered List'">
                <variablelist>
                    <xsl:apply-templates/>
                </variablelist>
            </xsl:when>
            <xsl:otherwise>
                <itemizedlist>
                    <title/>
                    <xsl:apply-templates/>
                </itemizedlist>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    

    <xsl:template match="text:list-item">
        <xsl:if test="parent::text:unordered-list/@text:style-name='Var List'">
            <varlistentry>
                <xsl:for-each select="text:p[@text:style-name='VarList Term']">
                    <xsl:apply-templates select="."/>
                </xsl:for-each>
            </varlistentry>
        </xsl:if>
        <xsl:if test="not(parent::text:unordered-list/@text:style-name='Var List')">
            <listitem>
                <xsl:apply-templates/>
            </listitem>
        </xsl:if>
    </xsl:template>

    
    <xsl:template match="draw:image">
        <xsl:choose>
            <xsl:when test="parent::text:p[@text:style-name='Mediaobject']">
            </xsl:when>
            <xsl:otherwise>
                <xsl:element name="inlinegraphic">
                    <xsl:attribute name="fileref">
                    	<xsl:choose>
	                    	<xsl:when test="starts-with(@xlink:href, '#Pictures/')">{TX_TERDOC_PICTURESDIR}<xsl:value-of select="substring(@xlink:href,11)"/></xsl:when>
                    	</xsl:choose>
                    </xsl:attribute>
                    <xsl:attribute name="width"/>
                </xsl:element>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    
    <xsl:template match="text:span">
        <xsl:choose>
            <xsl:when test="@text:style-name='Emphasis'">
                <xsl:element name="emphasis">
			<xsl:apply-templates/>
                </xsl:element>
            </xsl:when>
			<xsl:when test="@text:style-name='Strong Emphasis'">
				<xsl:element name="emphasis">
					<xsl:attribute name = "role" >
						<xsl:text >bold</xsl:text>
					</xsl:attribute>
					<xsl:apply-templates/>
				</xsl:element>
			</xsl:when>
			<xsl:when test="@text:style-name='Emphasis Bold'">
				<xsl:element name="emphasis">
					<xsl:attribute name = "role" >
						<xsl:text >bold</xsl:text>
					</xsl:attribute>
					<xsl:apply-templates/>
				</xsl:element>
			</xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

	<xsl:template match="text:s">
		<xsl:call-template name="write-breakable-whitespace">
			<xsl:with-param name="whitespaces" select="@text:c" />
		</xsl:call-template>
	</xsl:template>


	<!--write the number of 'whitespaces' -->
	<xsl:template name="write-breakable-whitespace">
		<xsl:param name="whitespaces" />

		<!--write two space chars as the normal white space character will be stripped
			and the other is able to break -->
		<xsl:text>&#160;</xsl:text>
		<xsl:if test="$whitespaces >= 2">
			<xsl:call-template name="write-breakable-whitespace-2">
				<xsl:with-param name="whitespaces" select="$whitespaces - 1" />
			</xsl:call-template>
		</xsl:if>
	</xsl:template>


	<!--write the number of 'whitespaces' -->
	<xsl:template name="write-breakable-whitespace-2">
		<xsl:param name="whitespaces" />
		<!--write two space chars as the normal white space character will be stripped
			and the other is able to break -->
		<xsl:text> </xsl:text>
		<xsl:if test="$whitespaces >= 2">
			<xsl:call-template name="write-breakable-whitespace">
				<xsl:with-param name="whitespaces" select="$whitespaces - 1" />
			</xsl:call-template>
		</xsl:if>
	</xsl:template>


    <xsl:template match="text:a">
        <xsl:choose>
            <xsl:when test="contains(@xlink:href,'://')">
                <xsl:element name="ulink">
                    <xsl:attribute name="url">
                        <xsl:value-of select="@xlink:href"/>
                    </xsl:attribute>
                    <xsl:apply-templates/>
                </xsl:element>
            </xsl:when>
            <xsl:when test="not(contains(@xlink:href,'#'))">
                <xsl:element name="olink">
                    <xsl:attribute name="targetdocent">
                        <xsl:value-of select="@xlink:href"/>
                    </xsl:attribute>
                    <xsl:apply-templates/>
                </xsl:element>
            </xsl:when>
            <xsl:otherwise>
                <xsl:variable name="linkvar" select="substring-after(@xlink:href,'#')"/>
                <xsl:element name="link">
                    <xsl:attribute name="linkend">
                        <xsl:value-of select="substring-before($linkvar,'%')"/>
                    </xsl:attribute>
                    <xsl:apply-templates/>
                </xsl:element>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    
    <xsl:template match="text:line-break">
        <xsl:text disable-output-escaping="yes"/>
    </xsl:template>

    
    <xsl:template name="id.attribute">
        <xsl:if test="child::text:reference-mark-start">
            <xsl:attribute name="id">
                <xsl:value-of select="child::text:reference-mark-start/@text:name"/>
            </xsl:attribute>
        </xsl:if>
    </xsl:template>
    
</xsl:stylesheet>

