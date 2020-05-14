<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:i="http://www.iupac.org/namespaces/ThermoML"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/2001/XMLSchema" 
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xmlns:sm="http://springermaterials.com" exclude-result-prefixes="xs" version="2.0">
	<xsl:output method="xml" omit-xml-declaration="yes" indent="yes"/>
	<xsl:strip-space elements="*"/>
	<xsl:template match="/i:DataReport">
	{
		<xsl:variable name="compds" select="//Compound"/>
		"compounds": [
		<xsl:for-each select="$compds">
			{
				"name": "<xsl:value-of select="./sCommonName"/>"
			}
		</xsl:for-each>
		]
	}
    </xsl:template>
	<!-- Functions -->
	<xsl:function name="sm:gettype">
		<xsl:param name="value" as="item()"/>
		<xsl:variable name="datatype" as="xs:string">
			<xsl:choose>
				<xsl:when test="string($value) castable as xs:date">
					<xsl:value-of select="'date'"/>
				</xsl:when>
				<xsl:when test="string($value) castable as xs:integer">
					<xsl:value-of select="'integer'"/>
				</xsl:when>
				<xsl:when test="string($value) castable as xs:float">
					<xsl:value-of select="'float'"/>
				</xsl:when>
				<xsl:when test="string($value) castable as xs:decimal">
					<xsl:value-of select="'decimal'"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="'string'"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$datatype"/>
	</xsl:function>
	<xsl:function name="sm:equality">
		<xsl:param name="equalstr" as="xs:string"/>
		<xsl:variable name="equality" as="xs:string">
			<xsl:choose>
				<xsl:when test="contains($equalstr,' = ') or starts-with($equalstr,'=')">
					<xsl:value-of select="'='"/>
				</xsl:when>
				<xsl:when test="contains($equalstr,' ~ ') or starts-with($equalstr,'~')">
					<xsl:value-of select="'~'"/>
				</xsl:when>
				<xsl:when test="contains($equalstr,' &lt; ') or starts-with($equalstr,'&lt;')">
					<xsl:value-of select="'&lt;'"/>
				</xsl:when>
				<xsl:when test="contains($equalstr,' &gt; ') or starts-with($equalstr,'&gt;')">
					<xsl:value-of select="'&gt;'"/>
				</xsl:when>
				<xsl:when test="contains($equalstr,' &gt;= ') or starts-with($equalstr,'&gt;=')">
					<xsl:value-of select="'&gt;='"/>
				</xsl:when>
				<xsl:when test="contains($equalstr,' &lt;= ') or starts-with($equalstr,'&lt;=')">
					<xsl:value-of select="'&lt;='"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="''"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$equality"/>
	</xsl:function>
	<xsl:function name="sm:condition">
		<xsl:param name="condstr" as="xs:string"/>
		<xsl:variable name="condition">
			<xsl:choose>
				<xsl:when test="contains($condstr,'·10^')">
					<xsl:value-of select="tokenize($condstr,'·')[position() = 1]"/>
				</xsl:when>
				<xsl:when test="contains($condstr,'/')">
					<xsl:value-of select="replace($condstr,'/','**')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$condstr"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$condition"/>
	</xsl:function>
	<xsl:function name="sm:dsoffset">
		<xsl:param name="dspos" as="xs:integer"/>
		<xsl:param name="dstotals"/>
		<xsl:variable name="dsoffset">
			<xsl:choose>
				<xsl:when test="$dspos=1">
					<xsl:value-of select="0"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$dstotals[$dspos - 1]"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$dsoffset"/>
	</xsl:function>
	<xsl:function name="sm:sercounts" as="xs:integer*">
		<xsl:param name="dataset" as="item()"/>
		<xsl:variable name="sercounts" as="xs:integer*">
			<xsl:for-each select="$dataset/ser">
				<xsl:variable name="numscs" as="xs:integer">
					<xsl:choose>
						<xsl:when test="count(sc)>0">
							<xsl:value-of select="1"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="0"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="numrows" as="xs:integer">
					<xsl:choose>
						<xsl:when test="c[1]">
							<xsl:value-of select="count(c[1]/chunk)"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="count(d[1]/chunk)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="numnotes" select="count(note)" as="xs:integer"/>
				<xsl:variable name="numeqns" select="count(eqn)" as="xs:integer"/>
				<xsl:variable name="numanns" select="count(a[not(*)])" as="xs:integer"/>
				<xsl:value-of select="$numscs+$numrows+$numnotes+$numeqns+$numanns" />
			</xsl:for-each>
		</xsl:variable>
		<xsl:value-of select="$sercounts"/>
	</xsl:function>
	<xsl:function name="sm:sertotals" as="xs:integer*">
		<xsl:param name="sercounts"/>
		<xsl:variable name="sertotals" as="xs:integer*">
			<xsl:for-each select="1 to count($sercounts)">
				<xsl:variable name="pos" select="." as="xs:integer"/>
				<xsl:value-of select="sum($sercounts[position() &lt;= $pos])"/>
			</xsl:for-each>
		</xsl:variable>
		<xsl:value-of select="$sertotals"/>
	</xsl:function>
	<xsl:function name="sm:factor" as="xs:float">
		<xsl:param name="condstr" as="xs:string"/>
		<xsl:variable name="factor">
			<xsl:choose>
				<xsl:when test="contains($condstr,'·10^')">
					<xsl:variable name="temp" select="xs:float(replace(tokenize($condstr,'·')[position() = 2],'0\^','E'))" as="xs:float"/>
					<xsl:value-of select="$temp"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="1"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$factor"/>
	</xsl:function>
	<xsl:function name="sm:max">
		<xsl:param name="numberstr" as="xs:string"/>
		<xsl:variable name="temp" select="replace(replace($numberstr,',','.'),' ','')" as="xs:string"/>
		<xsl:variable name="temp2" as="xs:string">
			<xsl:choose>
				<xsl:when test="contains($temp,'···')">
					<xsl:value-of select="tokenize($temp,'···')[position()=2]"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:sequence select="'0'"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="max">
			<xsl:choose>
				<xsl:when test="contains($temp2,'·10^')">
					<xsl:value-of select="replace($temp2,'·10\^','E')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$temp2"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$max"/>
	</xsl:function>
	<xsl:function name="sm:numberstr">
		<xsl:param name="valuestr" as="xs:string"/>
		<xsl:variable name="numberstr">
			<xsl:choose>
				<xsl:when test="$valuestr!=''">
					<xsl:choose>
						<xsl:when test="contains($valuestr,' ± ')">
							<xsl:value-of select="tokenize($valuestr,' ± ')[1]"/>
						</xsl:when>
						<xsl:when test="contains($valuestr,'±')">
							<xsl:value-of select="tokenize($valuestr,'±')[1]"/>
						</xsl:when>
						<xsl:when test="contains($valuestr,'°C (Z.-T.)')">
							<xsl:value-of select="replace($valuestr,'°C \(Z\.\-T\.\)','')"/>
						</xsl:when>
						<xsl:when test="contains($valuestr,'degC (Z.-T.)')">
							<xsl:value-of select="replace($valuestr,'degC \(Z\.\-T\.\)','')"/>
						</xsl:when>
						<xsl:when test="$valuestr='-' or $valuestr='−' or $valuestr=''">
							<xsl:value-of select="'-'"/>
						</xsl:when>
						<xsl:when test="matches($valuestr,'^[A-Za-z\s\(\)]+$')">
							<xsl:value-of select="$valuestr"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:analyze-string select="$valuestr" regex="^([\-\+0-9,·\.\^E]+).*$">
								<xsl:matching-substring>
									<xsl:value-of select="regex-group(1)"/>
								</xsl:matching-substring>
							</xsl:analyze-string>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="''"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$numberstr"/>
	</xsl:function>
	<xsl:function name="sm:number">
		<xsl:param name="numberstr" as="xs:string"/>
		<xsl:variable name="number">
			<xsl:choose>
				<xsl:when test="matches($numberstr,'^[A-Za-z\s\(\)]+$')">
					<xsl:value-of select="$numberstr"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="temp" select="replace(replace($numberstr,',','.'),' ','')" as="xs:string"/>
					<xsl:variable name="temp2">
						<xsl:choose>
							<xsl:when test="contains($temp,'···')">
								<xsl:value-of select="tokenize($temp,'···')[position()=1]"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$temp"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:choose>
						<xsl:when test="contains($temp2,'·10^')">
							<xsl:value-of select="replace($temp2,'·10\^','E')"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$temp2"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$number"/>
	</xsl:function>
	<xsl:function name="sm:formatnumber">
		<xsl:param name="number" as="xs:string"/>
		<xsl:param name="factor"/>
		<xsl:choose>
			<xsl:when test="matches($number,'-?[0-9]+\.[0-9E\-\+]+')">
				<xsl:variable name="sf" as="xs:integer">
					<xsl:choose>
						<xsl:when test="substring($number,1,1)='0'">
							<xsl:value-of select="string-length(tokenize($number,'E')[1]) - 2"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="string-length(tokenize($number,'E')[1]) - 1"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="value" select="number($number)"/>
				<xsl:variable name="newsf" select="string-length(tokenize(string($value),'E')[1])-1"/>
				<xsl:choose>
					<xsl:when test="$newsf=$sf">
						<xsl:value-of select="$value"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:variable name="pad" select="sm:string-pad('0',$sf - $newsf)"/>
						<xsl:value-of select="replace(string($value),'E',concat($pad,'E'))"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="matches($number,'-?[0-9]+E\-?\+?[0-9]+')">
				<!-- assumes string was concat of # with decimal and header exponent, so trailing zeros considered just placeholders -->
				<xsl:variable name="sf" select="string-length(tokenize($number,'E')[1])"/>
				<xsl:variable name="value" select="number(replace($number,'E','.E'))"/>
				<xsl:variable name="newsf" select="string-length(tokenize(string($value),'E')[1])-1"/>
				<xsl:choose>
					<xsl:when test="$newsf=$sf">
						<xsl:value-of select="$value"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:variable name="pad" select="sm:string-pad('0',$sf - $newsf)"/>
						<xsl:value-of select="replace(string($value),'E',concat($pad,'E'))"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:variable name="fnumber">
					<xsl:choose>
						<xsl:when test="$number='-' or $number='−' or matches($number,'/') or $number=''">
							<!-- $fnumber does not get used when $number is '-' -->
							<xsl:value-of select="0"/>
						</xsl:when>
						<xsl:when test="matches($number,'[A-Za-z\s\)\(]+')">
							<xsl:value-of select="$number"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="xs:float($number)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="left" as="xs:string">
					<xsl:choose>
						<xsl:when test="contains($number,'.')">
							<xsl:value-of select="tokenize(replace($number,'-',''),'\.')[position()=1]"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="replace($number,'-','')"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="right" as="xs:string">
					<xsl:choose>
						<xsl:when test="contains($number,'.')">
							<xsl:value-of select="tokenize($number,'\.')[position()=2]"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="''"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="leftcount" as="xs:integer">
					<xsl:choose>
						<xsl:when test="$left='0'">
							<xsl:value-of select="0"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="string-length($left)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="rightcount" as="xs:integer">
					<xsl:choose>
						<xsl:when test="($leftcount &gt;= 1) or ($leftcount &lt;= -1)">
							<xsl:value-of select="string-length($right)"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:variable name="zeros" select="replace($right,'(0*)(.+)','$1')"/>
							<xsl:value-of select="string-length($right) - string-length($zeros)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="sf" select="$leftcount + $rightcount" as="xs:integer"/>
				<xsl:variable name="answer">
					<xsl:choose>
						<xsl:when test="xs:float($factor)=1">
							<xsl:value-of select="sm:scinot($number,$sf)"/>
						</xsl:when>
						<xsl:when test="$number='-' or $number='−'">
							<xsl:value-of select="'-'"/>
						</xsl:when>
						<xsl:otherwise>
							<!-- sometimes when a number is divided by 1000 it is not rounded correctly :( -->
							<xsl:variable name="temp" select="$fnumber div $factor"/>
							<xsl:choose>
								<xsl:when test="($temp &lt; 1.0) and ($temp &gt; 0.0)">
									<xsl:variable name="zeros" select="replace(xs:string($temp),'\.(0*)(.+)','$1')"/>
									<xsl:variable name="temp2" select="round-half-to-even($temp,string-length($zeros) + $sf)"/>
									<xsl:value-of select="sm:scinot(xs:string($temp2),$sf)"/>
								</xsl:when>
								<xsl:when test="($temp &lt; 0.0) and ($temp &gt; -1.0)">
									<xsl:variable name="zeros" select="replace(xs:string($temp),'\.(0*)(.+)','$1')"/>
									<xsl:variable name="temp2" select="round-half-to-even($temp,string-length($zeros) + $sf)"/>
									<xsl:value-of select="sm:scinot(xs:string($temp2),$sf)"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="sm:scinot(xs:string($fnumber div $factor),$sf)"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:value-of select="$answer"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:function>
	<xsl:function name="sm:scinot">
		<xsl:param name="rawnum"/>
		<xsl:param name="sf"/>
		<xsl:variable name="scinot" as="xs:string">
			<xsl:choose>
				<xsl:when test="contains($rawnum,'E')">
					<xsl:variable name="man"  select="tokenize($rawnum,'E')[1]"/>
					<xsl:variable name="exp"  select="tokenize($rawnum,'E')[2]"/>
					<xsl:variable name="sign" as="xs:string">
						<xsl:choose>
							<xsl:when test="substring($man,1,1)='-' or substring($man,1,1)='−'">
								<xsl:value-of select="'-'"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="''"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:variable name="abs">
						<xsl:choose>
							<xsl:when test="substring($man,1,1)='-'">
								<xsl:value-of select="replace($man,'-','')"/>
							</xsl:when>
							<xsl:when test="substring($man,1,1)='−'">
								<xsl:value-of select="replace($man,'−','')"/>
							</xsl:when>
							<xsl:when test="substring($man,1,1)='+'">
								<xsl:value-of select="''"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="''"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:variable name="temp" select="substring($abs,1,$sf + 1)"/>
					<xsl:value-of select="concat($sign,$temp,'E',$exp)"/>
				</xsl:when>
				<xsl:when test="$rawnum='-' or $rawnum='−'">
					<xsl:value-of select="$rawnum"/>
				</xsl:when>
				<xsl:when test="matches($rawnum,'[A-Za-z\s\)\(]+')">
					<xsl:value-of select="$rawnum"/>
				</xsl:when>
				<xsl:when test="matches($rawnum,'/')">
					<xsl:value-of select="$rawnum"/>
				</xsl:when>
				<xsl:when test="xs:float($rawnum)=0">
					<xsl:value-of select="$rawnum"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="xs:float($rawnum) &lt;= -1">
							<!-- negative number <= -1 -->
							<xsl:variable name="left" as="xs:string">
								<xsl:choose>
									<xsl:when test="contains($rawnum,'.')">
										<xsl:value-of select="tokenize($rawnum,'\.')[position()=1]"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="$rawnum"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:variable>
							<!-- -2 (not -1) here as there is a negative sign -->
							<xsl:variable name="exp" select="sum(string-length($left) - 2)"/>
							<xsl:variable name="digits" select="replace($rawnum,'\.','')"/>
							<!-- add 1 to sf because of negative sign -->
							<xsl:variable name="padded" select="sm:pad-string($digits,'0',$sf + 1)"/>
							<!-- put first digit and minus sign before period -->
							<xsl:variable name="mantissa" select="concat(substring($padded,1,2),'.',substring($padded,3,$sf - 1))"/>
							<xsl:value-of select="concat($mantissa,'E',$exp)"/>
						</xsl:when>
						<xsl:when test="xs:float($rawnum) lt 0">
							<!-- negative number > -1 but < 0 -->
							<xsl:variable name="right" select="tokenize($rawnum,'\.')[position()=2]" as="xs:string"/>
							<xsl:variable name="zeros" select="replace($right,'(0*)(.+)','$1')"/>
							<xsl:variable name="digits" select="xs:string(number($right))"/>
							<xsl:variable name="padded" select="sm:pad-string($digits,'0',$sf)"/>
							<xsl:variable name="exp" select="string-length($zeros) + 1"/>
							<!-- take first digit and minus sign before period -->
							<xsl:variable name="mantissa" select="concat(substring($padded,1,1),'.',substring($padded,2,$sf - 1))"/>
							<xsl:value-of select="concat('-',$mantissa,'E-',$exp)"/>
						</xsl:when>
						<xsl:when test="xs:float($rawnum) &gt;= 1">
							<!-- positive number >= 1 -->
							<xsl:variable name="left" as="xs:string">
								<xsl:choose>
									<xsl:when test="contains($rawnum,'.')">
										<xsl:value-of select="tokenize($rawnum,'\.')[position()=1]"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="$rawnum"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:variable>
							<xsl:variable name="exp" select="string-length($left) - 1"/>
							<xsl:variable name="digits" select="replace($rawnum,'\.','')"/>
							<xsl:variable name="padded" select="sm:pad-string($digits,'0',$sf)"/>
							<xsl:variable name="mantissa" select="concat(substring($padded,1,1),'.',substring($padded,2,$sf - 1))"/>
							<xsl:value-of select="concat($mantissa,'E',$exp)"/>
						</xsl:when>
						<xsl:when test="xs:float($rawnum) gt 0">
							<!-- positive number > 0 but < 1 -->
							<xsl:variable name="right" select="tokenize($rawnum,'\.')[position()=2]" as="xs:string"/>
							<xsl:variable name="zeros" select="replace($right,'(0*)(.+)','$1')"/>
							<xsl:variable name="digits" select="xs:string(number($right))"/>
							<xsl:variable name="padded" select="sm:pad-string($digits,'0',$sf)"/>
							<xsl:variable name="exp" select="string-length($zeros) + 1"/>
							<xsl:variable name="mantissa" select="concat(substring($padded,1,1),'.',substring($padded,2,$sf - 1))"/>
							<xsl:value-of select="concat($mantissa,'E-',$exp)"/>
						</xsl:when>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$scinot"/>
	</xsl:function>
	<xsl:function name="sm:errorstr">
		<xsl:param name="valuestr" as="xs:string"/>
		<xsl:variable name="errorstr">
			<xsl:choose>
				<xsl:when test="contains($valuestr,'±')">
					<xsl:analyze-string select="$valuestr" regex="^[\-0-9,·\.\^E]+\s?±\s?([\-\+0-9,·\.\^E%]+)\s?.*$">
						<xsl:matching-substring>
							<xsl:value-of select="regex-group(1)"/>
						</xsl:matching-substring>
					</xsl:analyze-string>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="''"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$errorstr"/>
	</xsl:function>
	<xsl:function name="sm:error">
		<xsl:param name="errorstr" as="xs:string"/>
			<xsl:variable name="error">
				<xsl:choose>
					<xsl:when test="contains($errorstr,'·10^')">
						<xsl:value-of select="replace(replace($errorstr,'·10\^','E'),',','.')"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="replace($errorstr,',','.')"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
		<xsl:value-of select="$error"/>
	</xsl:function>
	<xsl:function name="sm:unitstr" as="xs:string">
		<xsl:param name="valuestr" as="xs:string?"/>
		<xsl:variable name="unitstr">
			<xsl:choose>
				<xsl:when test="contains($valuestr,'°C (Z.-T.)')">
					<xsl:value-of select="'°C'"/>
				</xsl:when>
				<xsl:when test="contains($valuestr,'degC (Z.-T.)')">
					<xsl:value-of select="'degC'"/>
				</xsl:when>
				<xsl:when test="contains($valuestr,'±')">
					<xsl:analyze-string select="$valuestr" regex="^[\-\+0-9,·\.\^E]+\s±\s[\-\+0-9,·\.\^E%]+\s?(.*)$">
						<xsl:matching-substring>
							<xsl:value-of select="regex-group(1)"/>
						</xsl:matching-substring>
					</xsl:analyze-string>
				</xsl:when>
				<xsl:when test="contains($valuestr,'&lt;')">
					<xsl:analyze-string select="$valuestr" regex="^[\-\+0-9,·\.\^E]+\s&lt;\s?(.*)$">
						<xsl:matching-substring>
							<xsl:value-of select="regex-group(1)"/>
						</xsl:matching-substring>
					</xsl:analyze-string>
				</xsl:when>
				<xsl:when test="contains($valuestr,'&gt;')">
					<xsl:analyze-string select="$valuestr" regex="^[\-\+0-9,·\.\^E]+\s&gt;\s?(.*)$">
						<xsl:matching-substring>
							<xsl:value-of select="regex-group(1)"/>
						</xsl:matching-substring>
					</xsl:analyze-string>
				</xsl:when>
				<xsl:when test="contains($valuestr,' ')">
					<xsl:analyze-string select="$valuestr" regex="^[\-\+0-9,·\.\^E]+\s(.*)$">
						<xsl:matching-substring>
							<xsl:value-of select="regex-group(1)"/>
						</xsl:matching-substring>
					</xsl:analyze-string>
				</xsl:when>
				<xsl:otherwise>
					<!-- Only °C, °F, and % should be without a space -->
					<xsl:analyze-string select="$valuestr" regex="^[\-\+0-9,·\.\^E]+(.*)$">
						<xsl:matching-substring>
							<xsl:choose>
								<xsl:when test="regex-group(1)=''">
									<xsl:value-of select="'unitless'"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="regex-group(1)"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:matching-substring>
					</xsl:analyze-string>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$unitstr"/>
	</xsl:function>
	<xsl:function name="sm:unit" as="xs:string">
		<xsl:param name="unitstr" as="xs:string?"/>
		<xsl:variable name="unit">
			<xsl:choose>
				<xsl:when test="$unitstr=''">
					<xsl:value-of select="'unitless'"/>
				</xsl:when>
				<xsl:when test="matches($unitstr,' X[0-9] \+ X[0-9]')">
					<xsl:value-of select="replace(replace($unitstr,'\sX[0-9]\s\+\sX[0-9]',''),'/','**')"/>
				</xsl:when>
				<xsl:when test="matches($unitstr,' X[0-9]')">
					<xsl:value-of select="replace(replace($unitstr,'\sX[0-9]',''),'/','**')"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="replace($unitstr,'/','**')"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$unit"/>
	</xsl:function>
	<xsl:function name="sm:pad-string" as="xs:string">
		<xsl:param name="stringToPad" as="xs:string?"/>
		<xsl:param name="padChar" as="xs:string"/>
		<xsl:param name="length" as="xs:integer"/>
		<xsl:sequence select="
			substring(
			string-join (
			($stringToPad, for $i in (1 to $length) return $padChar),''),1,$length)
			"/>
	</xsl:function>
	<xsl:function name="sm:nonr" as="xs:string">
		<xsl:param name="str" as="xs:string?"/>
		<xsl:value-of select="replace(translate($str,'&#9;&#10;', ' '),'\s+',' ')"/>
	</xsl:function>
	<xsl:function name="sm:string-pad" as="xs:string">
		<xsl:param name="padString" as="xs:string?"/>
		<xsl:param name="padCount" as="xs:integer"/>
		<xsl:sequence select="string-join(for $i in 1 to $padCount return $padString)"/>
	</xsl:function>
</xsl:stylesheet>
