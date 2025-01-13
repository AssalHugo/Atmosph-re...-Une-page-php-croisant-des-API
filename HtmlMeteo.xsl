<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!-- Spécifier la sortie en HTML -->
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>

    <!-- Point d'entrée -->
    <xsl:template match="/">
        <html>
            <head>
                <title>Météo du jour</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .symbol { font-size: 20px; margin-right: 10px; }
                </style>
            </head>
            <body>
                <h1>Prévisions météorologiques</h1>
                <div>
                    <xsl:apply-templates select="//echeance[@hour='6']"/>
                    <xsl:apply-templates select="//echeance[@hour='12']"/>
                    <xsl:apply-templates select="//echeance[@hour='18']"/>
                </div>
            </body>
        </html>
    </xsl:template>

    <!-- Template pour chaque tranche horaire -->
    <xsl:template match="echeance">
        <h2>
            <xsl:choose>
                <xsl:when test="@hour='6'">Matin</xsl:when>
                <xsl:when test="@hour='12'">Midi</xsl:when>
                <xsl:when test="@hour='18'">Soir</xsl:when>
            </xsl:choose>
        </h2>
        <ul>
            <!-- Température -->
            <li>
                <span class="symbol">&#x2744;</span>
                <xsl:choose>
                    <xsl:when test="temperature/level[@val='2m'] &lt; 273">Froid</xsl:when>
                    <xsl:otherwise>Tempéré</xsl:otherwise>
                </xsl:choose>
            </li>

            <!-- Pluie -->
            <li>
                <span class="symbol">&#x1F327;</span>
                <xsl:choose>
                    <xsl:when test="pluie &gt; 0">Pluie attendue</xsl:when>
                    <xsl:otherwise>Pas de pluie</xsl:otherwise>
                </xsl:choose>
            </li>

            <!-- Risque de neige -->
            <li>
                <span class="symbol">&#x1F328;</span>
                <xsl:choose>
                    <xsl:when test="risque_neige='oui'">Risque de neige</xsl:when>
                    <xsl:otherwise>Pas de neige</xsl:otherwise>
                </xsl:choose>
            </li>

            <!-- Vent -->
            <li>
                <span class="symbol">&#x1F32C;</span>
                <xsl:choose>
                    <xsl:when test="vent_moyen/level[@val='10m'] &gt; 25">Vent fort</xsl:when>
                    <xsl:otherwise>Vent calme</xsl:otherwise>
                </xsl:choose>
            </li>
        </ul>
    </xsl:template>

</xsl:stylesheet>
