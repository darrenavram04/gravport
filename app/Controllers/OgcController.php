<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * OgcController — OGC Web Services
 *
 * Implements basic OGC-compliant endpoints:
 *   GET /ogc          → HTML landing page
 *   GET /ogc/wms      → WMS 1.3.0 (GetCapabilities, GetMap)
 *   GET /ogc/wfs      → WFS 2.0.0 (GetCapabilities, GetFeature → GeoJSON)
 *   GET /ogc/csw      → CSW 2.0.2 (GetCapabilities, GetRecords → Dublin Core XML)
 *
 * All spatial data is in EPSG:4326 (WGS84). Data coverage: Java-Bali, Indonesia.
 *
 * QGIS usage:
 *   WMS: Layer > Add Layer > Add WMS/WMTS Layer → URL: {base}/ogc/wms
 *   WFS: Layer > Add Layer > Add WFS Layer      → URL: {base}/ogc/wfs
 */
class OgcController extends BaseController
{
    // ── Bounding boxes ────────────────────────────────────────────
    private const BBOX_JAVA = [105.0, -9.0, 116.0, -5.5];  // Java-Bali
    private const MAX_EXTENT = 2.0;  // Max degrees per WMS GetMap request

    // ── Layer definitions ─────────────────────────────────────────
    private array $layers = [
        'gravity_faa' => [
            'name'     => 'gravport:gravity_faa',
            'title'    => 'Free Air Anomaly (FAA) — GravPort',
            'abstract' => 'Gravity Free Air Anomaly points from GravPort dataset. Data Level 1. CRS: EPSG:4326.',
            'anom'     => 'FAA',
            'level'    => 1,
        ],
        'gravity_cba' => [
            'name'     => 'gravport:gravity_cba',
            'title'    => 'Complete Bouguer Anomaly (CBA) — GravPort',
            'abstract' => 'Gravity Complete Bouguer Anomaly points from GravPort dataset. Data Level 1. CRS: EPSG:4326.',
            'anom'     => 'CBA',
            'level'    => 1,
        ],
    ];

    // ════════════════════════════════════════════════════════════════
    // OGC Landing Page
    // ════════════════════════════════════════════════════════════════
    public function landing(): ResponseInterface|string
    {
        $wmsUrl = site_url('ogc/wms') . '?SERVICE=WMS&REQUEST=GetCapabilities&VERSION=1.3.0';
        $wfsUrl = site_url('ogc/wfs') . '?SERVICE=WFS&REQUEST=GetCapabilities&VERSION=2.0.0';
        $cswUrl = site_url('ogc/csw') . '?SERVICE=CSW&REQUEST=GetCapabilities&VERSION=2.0.2';

        return view('v_ogc_landing', compact('wmsUrl', 'wfsUrl', 'cswUrl'));
    }

    // ════════════════════════════════════════════════════════════════
    // WMS — Web Map Service 1.3.0
    // ════════════════════════════════════════════════════════════════
    public function wms(): ResponseInterface
    {
        $service = strtoupper($this->request->getGet('SERVICE') ?? '');
        $request = strtoupper($this->request->getGet('REQUEST') ?? 'GETCAPABILITIES');

        if ($service && $service !== 'WMS') {
            return $this->xmlError('InvalidParameter', 'SERVICE must be WMS');
        }

        return match ($request) {
            'GETCAPABILITIES' => $this->wmsCapabilities(),
            'GETMAP'          => $this->wmsGetMap(),
            default           => $this->xmlError('OperationNotSupported', "WMS request '{$request}' is not supported. Supported: GetCapabilities, GetMap"),
        };
    }

    private function wmsCapabilities(): ResponseInterface
    {
        $base = site_url('ogc/wms');
        [$minx, $miny, $maxx, $maxy] = self::BBOX_JAVA;

        $layersXml = '';
        foreach ($this->layers as $id => $layer) {
            // WMS 1.3.0 EPSG:4326 axis order: latitude first (minx=minLat, miny=minLon)
            $layersXml .= <<<XML
    <Layer queryable="0" opaque="0">
      <Name>{$layer['name']}</Name>
      <Title>{$layer['title']}</Title>
      <Abstract>{$layer['abstract']}</Abstract>
      <CRS>EPSG:4326</CRS>
      <CRS>CRS:84</CRS>
      <EX_GeographicBoundingBox>
        <westBoundLongitude>{$minx}</westBoundLongitude>
        <eastBoundLongitude>{$maxx}</eastBoundLongitude>
        <southBoundLatitude>{$miny}</southBoundLatitude>
        <northBoundLatitude>{$maxy}</northBoundLatitude>
      </EX_GeographicBoundingBox>
      <BoundingBox CRS="EPSG:4326" minx="{$miny}" miny="{$minx}" maxx="{$maxy}" maxy="{$maxx}"/>
      <BoundingBox CRS="CRS:84"    minx="{$minx}" miny="{$miny}" maxx="{$maxx}" maxy="{$maxy}"/>
    </Layer>
XML;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<WMS_Capabilities version="1.3.0" xmlns="http://www.opengis.net/wms"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opengis.net/wms http://schemas.opengis.net/wms/1.3.0/capabilities_1_3_0.xsd">
  <Service>
    <Name>WMS</Name>
    <Title>GravPort Web Map Service</Title>
    <Abstract>OGC WMS 1.3.0 endpoint for GravPort gravity anomaly datasets. Coverage: Java-Bali, Indonesia. CRS: EPSG:4326 (WGS84).</Abstract>
    <KeywordList><Keyword>gravity</Keyword><Keyword>anomaly</Keyword><Keyword>geophysics</Keyword><Keyword>Java</Keyword><Keyword>Bali</Keyword></KeywordList>
    <OnlineResource xlink:href="{$base}"/>
    <ContactInformation>
      <ContactOrganization>GravPort</ContactOrganization>
      <ContactElectronicMailAddress>gravportadmin@gmail.com</ContactElectronicMailAddress>
    </ContactInformation>
    <Fees>Subscription required for data access (Pro+). See {$base}/account</Fees>
    <AccessConstraints>GravPort subscription required for GetMap. Free tier: browse catalog only.</AccessConstraints>
  </Service>
  <Capability>
    <Request>
      <GetCapabilities>
        <Format>text/xml</Format>
        <DCPType><HTTP><Get><OnlineResource xlink:href="{$base}?"/></Get></HTTP></DCPType>
      </GetCapabilities>
      <GetMap>
        <Format>image/png</Format>
        <DCPType><HTTP><Get><OnlineResource xlink:href="{$base}?"/></Get></HTTP></DCPType>
      </GetMap>
    </Request>
    <Exception><Format>XML</Format></Exception>
    <Layer>
      <Title>GravPort Gravity Layers</Title>
      <CRS>EPSG:4326</CRS>
      <CRS>CRS:84</CRS>
      <EX_GeographicBoundingBox>
        <westBoundLongitude>{$minx}</westBoundLongitude>
        <eastBoundLongitude>{$maxx}</eastBoundLongitude>
        <southBoundLatitude>{$miny}</southBoundLatitude>
        <northBoundLatitude>{$maxy}</northBoundLatitude>
      </EX_GeographicBoundingBox>
      {$layersXml}
    </Layer>
  </Capability>
</WMS_Capabilities>
XML;

        return $this->xmlResponse($xml);
    }

    private function wmsGetMap(): ResponseInterface
    {
        // ── Parse parameters ──────────────────────────────────────
        $layers  = $this->request->getGet('LAYERS') ?? $this->request->getGet('layers') ?? '';
        $bbox    = $this->request->getGet('BBOX')   ?? $this->request->getGet('bbox')   ?? '';
        $width   = (int)($this->request->getGet('WIDTH')  ?? $this->request->getGet('width')  ?? 256);
        $height  = (int)($this->request->getGet('HEIGHT') ?? $this->request->getGet('height') ?? 256);
        $crs     = strtoupper($this->request->getGet('CRS') ?? $this->request->getGet('SRS') ?? 'EPSG:4326');

        if (!$bbox) {
            return $this->xmlError('MissingParameterValue', 'BBOX is required');
        }

        $parts = explode(',', $bbox);
        if (count($parts) !== 4) {
            return $this->xmlError('InvalidParameterValue', 'BBOX must be minx,miny,maxx,maxy');
        }

        // Only EPSG:4326 is supported for GetMap (data is natively geographic WGS84)
        if (!in_array($crs, ['EPSG:4326', 'CRS:84'])) {
            return $this->xmlError('InvalidParameterValue',
                "CRS '{$crs}' is not supported for GetMap. Supported: EPSG:4326, CRS:84. " .
                "Data is natively EPSG:4326 (WGS84 geographic). " .
                "Set your GIS client CRS to EPSG:4326 to consume this WMS.");
        }

        // WMS 1.3.0: for EPSG:4326 axis order is lat,lon → BBOX = minlat,minlon,maxlat,maxlon
        // CRS:84 uses lon,lat order (same as WMS 1.1.x SRS convention)
        if ($crs === 'EPSG:4326') {
            [$miny, $minx, $maxy, $maxx] = array_map('floatval', $parts);
        } else {
            // CRS:84 — lon,lat order
            [$minx, $miny, $maxx, $maxy] = array_map('floatval', $parts);
        }

        // Validate extent
        if (($maxx - $minx) > self::MAX_EXTENT || ($maxy - $miny) > self::MAX_EXTENT) {
            return $this->xmlError('InvalidParameterValue', 'BBOX extent too large. Maximum ' . self::MAX_EXTENT . '° per axis.');
        }

        // Clamp dimensions
        $width  = max(64, min($width, 2048));
        $height = max(64, min($height, 2048));

        // ── Resolve anomaly type from layer name ──────────────────
        $anomType = 'FAA';
        $layerLower = strtolower($layers);
        if (str_contains($layerLower, 'cba')) {
            $anomType = 'CBA';
        } elseif (str_contains($layerLower, 'sba')) {
            $anomType = 'SBA';
        }

        // ── Query PostGIS ─────────────────────────────────────────
        $db  = \Config\Database::connect();
        $sql = "SELECT ST_X(p.geom) AS lon, ST_Y(p.geom) AS lat, p.point_value
                FROM geoportal.point_grav_anom p
                JOIN geoportal.datasets_grav_anom d ON d.dataset_id = p.dataset_id
                WHERE p.geom && ST_MakeEnvelope(?,?,?,?,4326)
                  AND d.dataset_anom_type = ?
                  AND d.dataset_level = 1
                LIMIT 8000";

        $rows = $db->query($sql, [$minx, $miny, $maxx, $maxy, $anomType])->getResultArray();

        // ── Determine value range for color mapping ───────────────
        $values = array_column($rows, 'point_value');
        $minVal = count($values) ? (float)min($values) : -100.0;
        $maxVal = count($values) ? (float)max($values) :  100.0;
        if ($maxVal <= $minVal) { $maxVal = $minVal + 1; }

        // ── Render PNG with PHP GD ────────────────────────────────
        $img = imagecreatetruecolor($width, $height);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);

        // Dot size (slightly larger for bigger tiles)
        $dotRadius = max(2, (int)($width / 64));

        foreach ($rows as $r) {
            $lon = (float)$r['lon'];
            $lat = (float)$r['lat'];
            $val = (float)$r['point_value'];

            // Coordinate → pixel
            $px = (int)(($lon - $minx) / ($maxx - $minx) * $width);
            $py = (int)(($maxy - $lat) / ($maxy - $miny) * $height);

            if ($px < 0 || $px >= $width || $py < 0 || $py >= $height) {
                continue;
            }

            // Value → color (blue=low, cyan=mid-low, green=mid, yellow=mid-high, red=high)
            $norm = ($val - $minVal) / ($maxVal - $minVal);
            $norm = max(0.0, min(1.0, $norm));
            [$r2, $g2, $b2] = $this->valueToRgb($norm);

            $color = imagecolorallocatealpha($img, $r2, $g2, $b2, 20);
            imagefilledellipse($img, $px, $py, $dotRadius * 2, $dotRadius * 2, $color);
        }

        // ── Output PNG ────────────────────────────────────────────
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        $response = service('response');
        $response->setStatusCode(200);
        $response->setContentType('image/png');
        $response->setHeader('X-GravPort-Points', (string)count($rows));
        $response->setHeader('X-GravPort-Layer', $anomType);
        $response->setBody($pngData);
        return $response;
    }

    // ════════════════════════════════════════════════════════════════
    // WFS — Web Feature Service 2.0.0
    // ════════════════════════════════════════════════════════════════
    public function wfs(): ResponseInterface
    {
        $service = strtoupper($this->request->getGet('SERVICE') ?? '');
        $request = strtoupper($this->request->getGet('REQUEST') ?? 'GETCAPABILITIES');

        if ($service && $service !== 'WFS') {
            return $this->xmlError('InvalidParameter', 'SERVICE must be WFS');
        }

        return match ($request) {
            'GETCAPABILITIES' => $this->wfsCapabilities(),
            'GETFEATURE'      => $this->wfsGetFeature(),
            'DESCRIBEFEATURETYPE' => $this->wfsDescribeFeatureType(),
            default           => $this->xmlError('OperationNotSupported', "WFS request '{$request}' not supported. Supported: GetCapabilities, GetFeature, DescribeFeatureType"),
        };
    }

    private function wfsCapabilities(): ResponseInterface
    {
        $base = site_url('ogc/wfs');
        [$minx, $miny, $maxx, $maxy] = self::BBOX_JAVA;

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<WFS_Capabilities version="2.0.0"
  xmlns="http://www.opengis.net/wfs/2.0"
  xmlns:ows="http://www.opengis.net/ows/1.1"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:gml="http://www.opengis.net/gml/3.2">
  <ows:ServiceIdentification>
    <ows:Title>GravPort Web Feature Service</ows:Title>
    <ows:Abstract>OGC WFS 2.0.0 — GravPort gravity anomaly point features. Java-Bali, Indonesia. CRS: EPSG:4326 (WGS84). Output: GeoJSON.</ows:Abstract>
    <ows:ServiceType>WFS</ows:ServiceType>
    <ows:ServiceTypeVersion>2.0.0</ows:ServiceTypeVersion>
  </ows:ServiceIdentification>
  <ows:OperationsMetadata>
    <ows:Operation name="GetCapabilities">
      <ows:DCP><ows:HTTP><ows:Get xlink:href="{$base}?"/></ows:HTTP></ows:DCP>
    </ows:Operation>
    <ows:Operation name="GetFeature">
      <ows:DCP><ows:HTTP><ows:Get xlink:href="{$base}?"/></ows:HTTP></ows:DCP>
      <ows:Parameter name="outputFormat">
        <ows:AllowedValues><ows:Value>application/json</ows:Value><ows:Value>text/xml</ows:Value></ows:AllowedValues>
      </ows:Parameter>
    </ows:Operation>
    <ows:Operation name="DescribeFeatureType">
      <ows:DCP><ows:HTTP><ows:Get xlink:href="{$base}?"/></ows:HTTP></ows:DCP>
    </ows:Operation>
  </ows:OperationsMetadata>
  <FeatureTypeList>
    <FeatureType>
      <Name>gravport:gravity_points</Name>
      <Title>GravPort Gravity Anomaly Points</Title>
      <Abstract>Gravity anomaly measurement points (FAA, CBA) — Level 1 data. Java-Bali, Indonesia.</Abstract>
      <DefaultCRS>urn:ogc:def:crs:EPSG::4326</DefaultCRS>
      <OtherCRS>urn:ogc:def:crs:EPSG::3857</OtherCRS>
      <ows:WGS84BoundingBox>
        <ows:LowerCorner>{$minx} {$miny}</ows:LowerCorner>
        <ows:UpperCorner>{$maxx} {$maxy}</ows:UpperCorner>
      </ows:WGS84BoundingBox>
    </FeatureType>
  </FeatureTypeList>
</WFS_Capabilities>
XML;

        return $this->xmlResponse($xml);
    }

    private function wfsDescribeFeatureType(): ResponseInterface
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<schema xmlns="http://www.w3.org/2001/XMLSchema"
        xmlns:gml="http://www.opengis.net/gml/3.2"
        xmlns:gravport="http://gravport.id/wfs"
        targetNamespace="http://gravport.id/wfs"
        elementFormDefault="qualified">
  <import namespace="http://www.opengis.net/gml/3.2"/>
  <element name="gravity_points" type="gravport:gravity_pointsType" substitutionGroup="gml:AbstractFeature"/>
  <complexType name="gravity_pointsType">
    <complexContent>
      <extension base="gml:AbstractFeatureType">
        <sequence>
          <element name="geom"           type="gml:PointPropertyType"/>
          <element name="point_id"       type="integer"/>
          <element name="point_value"    type="double"/>
          <element name="point_anom_type" type="string"/>
          <element name="point_obs_type"  type="string"/>
          <element name="data_level"      type="integer"/>
        </sequence>
      </extension>
    </complexContent>
  </complexType>
</schema>
XML;
        return $this->xmlResponse($xml);
    }

    private function wfsGetFeature(): ResponseInterface
    {
        // ── Parse BBOX ────────────────────────────────────────────
        $bbox = $this->request->getGet('BBOX') ?? $this->request->getGet('bbox') ?? '';
        $maxFeatures = min((int)($this->request->getGet('COUNT') ?? $this->request->getGet('MAXFEATURES') ?? 5000), 5000);
        $anomType = strtoupper($this->request->getGet('anom_type') ?? 'FAA');
        if (!in_array($anomType, ['FAA', 'CBA', 'SBA', 'BA', 'RAW'])) {
            $anomType = 'FAA';
        }

        $minx = 105.0; $miny = -9.0; $maxx = 116.0; $maxy = -5.5; // default: all Java-Bali
        if ($bbox) {
            $parts = explode(',', $bbox);
            if (count($parts) >= 4) {
                [$minx, $miny, $maxx, $maxy] = array_map('floatval', array_slice($parts, 0, 4));
            }
        }

        // Safety: max 2° extent per axis
        if (($maxx - $minx) > 2.0) { $maxx = $minx + 2.0; }
        if (($maxy - $miny) > 2.0) { $maxy = $miny + 2.0; }

        $db  = \Config\Database::connect();
        $sql = "SELECT p.point_id, ST_X(p.geom) AS lon, ST_Y(p.geom) AS lat,
                       p.point_value, d.dataset_anom_type AS point_anom_type,
                       p.point_obs_type, d.dataset_level AS data_level
                FROM geoportal.point_grav_anom p
                JOIN geoportal.datasets_grav_anom d ON d.dataset_id = p.dataset_id
                WHERE p.geom && ST_MakeEnvelope(?,?,?,?,4326)
                  AND d.dataset_anom_type = ?
                  AND d.dataset_level = 1
                LIMIT ?";

        $rows = $db->query($sql, [$minx, $miny, $maxx, $maxy, $anomType, $maxFeatures])->getResultArray();

        $outputFormat = strtolower($this->request->getGet('OUTPUTFORMAT') ?? 'application/json');

        if (str_contains($outputFormat, 'json')) {
            return $this->wfsGeoJson($rows, $minx, $miny, $maxx, $maxy, $anomType);
        }

        // XML / GML output
        return $this->wfsGml($rows, $minx, $miny, $maxx, $maxy);
    }

    private function wfsGeoJson(array $rows, float $minx, float $miny, float $maxx, float $maxy, string $anomType): ResponseInterface
    {
        $features = [];
        foreach ($rows as $r) {
            $features[] = [
                'type'       => 'Feature',
                'id'         => 'gravity_points.' . $r['point_id'],
                'geometry'   => [
                    'type'        => 'Point',
                    'coordinates' => [(float)$r['lon'], (float)$r['lat']],
                ],
                'properties' => [
                    'point_id'       => (int)$r['point_id'],
                    'point_value'    => (float)$r['point_value'],
                    'point_anom_type'=> $r['point_anom_type'],
                    'point_obs_type' => $r['point_obs_type'],
                    'data_level'     => (int)$r['data_level'],
                ],
            ];
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'name' => 'gravport:gravity_points',
            'crs'  => ['type' => 'name', 'properties' => ['name' => 'urn:ogc:def:crs:EPSG::4326']],
            'bbox' => [$minx, $miny, $maxx, $maxy],
            'numberMatched'  => count($features),
            'numberReturned' => count($features),
            'features'       => $features,
        ];

        $response = service('response');
        $response->setStatusCode(200);
        $response->setContentType('application/json; charset=utf-8');
        $response->setHeader('Content-Disposition', 'inline; filename="gravity_points.geojson"');
        $response->setBody(json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response;
    }

    private function wfsGml(array $rows, float $minx, float $miny, float $maxx, float $maxy): ResponseInterface
    {
        $members = '';
        foreach ($rows as $r) {
            $members .= sprintf(
                '<wfs:member><gravport:gravity_points gml:id="gravity_points.%d">
                  <gml:boundedBy><gml:Envelope srsName="EPSG:4326"><gml:lowerCorner>%s %s</gml:lowerCorner><gml:upperCorner>%s %s</gml:upperCorner></gml:Envelope></gml:boundedBy>
                  <gravport:geom><gml:Point srsName="EPSG:4326"><gml:pos>%s %s</gml:pos></gml:Point></gravport:geom>
                  <gravport:point_id>%d</gravport:point_id>
                  <gravport:point_value>%s</gravport:point_value>
                  <gravport:point_anom_type>%s</gravport:point_anom_type>
                  <gravport:data_level>%d</gravport:data_level>
                </gravport:gravity_points></wfs:member>',
                $r['point_id'], $r['lat'], $r['lon'], $r['lat'], $r['lon'],
                $r['lat'], $r['lon'], (int)$r['point_id'],
                $r['point_value'], $r['point_anom_type'], (int)$r['data_level']
            );
        }

        $count = count($rows);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<wfs:FeatureCollection xmlns:wfs="http://www.opengis.net/wfs/2.0"
  xmlns:gml="http://www.opengis.net/gml/3.2"
  xmlns:gravport="http://gravport.id/wfs"
  numberMatched="{$count}" numberReturned="{$count}">
  {$members}
</wfs:FeatureCollection>
XML;
        return $this->xmlResponse($xml);
    }

    // ════════════════════════════════════════════════════════════════
    // CSW — Catalog Service for the Web 2.0.2
    // ════════════════════════════════════════════════════════════════
    public function csw(): ResponseInterface
    {
        $service = strtoupper($this->request->getGet('SERVICE') ?? '');
        $request = strtoupper($this->request->getGet('REQUEST') ?? 'GETCAPABILITIES');

        if ($service && $service !== 'CSW') {
            return $this->xmlError('InvalidParameter', 'SERVICE must be CSW');
        }

        return match ($request) {
            'GETCAPABILITIES' => $this->cswCapabilities(),
            'GETRECORDS'      => $this->cswGetRecords(),
            'GETRECORDBYID'   => $this->cswGetRecordById(),
            default           => $this->xmlError('OperationNotSupported', "CSW request '{$request}' not supported. Supported: GetCapabilities, GetRecords, GetRecordById"),
        };
    }

    private function cswCapabilities(): ResponseInterface
    {
        $base = site_url('ogc/csw');
        $xml  = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<csw:Capabilities version="2.0.2"
  xmlns:csw="http://www.opengis.net/cat/csw/2.0.2"
  xmlns:ows="http://www.opengis.net/ows"
  xmlns:xlink="http://www.w3.org/1999/xlink">
  <ows:ServiceIdentification>
    <ows:Title>GravPort Catalog Service</ows:Title>
    <ows:Abstract>OGC CSW 2.0.2 metadata catalog for GravPort gravity anomaly datasets. Supports Ina-Geoportal / Satu Peta harvesting. ISO 19115 metadata.</ows:Abstract>
    <ows:ServiceType>CSW</ows:ServiceType>
    <ows:ServiceTypeVersion>2.0.2</ows:ServiceTypeVersion>
  </ows:ServiceIdentification>
  <ows:OperationsMetadata>
    <ows:Operation name="GetCapabilities">
      <ows:DCP><ows:HTTP><ows:Get xlink:href="{$base}?"/></ows:HTTP></ows:DCP>
    </ows:Operation>
    <ows:Operation name="GetRecords">
      <ows:DCP><ows:HTTP><ows:Get xlink:href="{$base}?"/></ows:HTTP></ows:DCP>
      <ows:Parameter name="outputSchema">
        <ows:Value>http://www.opengis.net/cat/csw/2.0.2</ows:Value>
      </ows:Parameter>
      <ows:Parameter name="typeNames">
        <ows:Value>csw:Record</ows:Value>
      </ows:Parameter>
    </ows:Operation>
    <ows:Operation name="GetRecordById">
      <ows:DCP><ows:HTTP><ows:Get xlink:href="{$base}?"/></ows:HTTP></ows:DCP>
    </ows:Operation>
  </ows:OperationsMetadata>
</csw:Capabilities>
XML;
        return $this->xmlResponse($xml);
    }

    private function cswGetRecords(): ResponseInterface
    {
        $maxRecords = min((int)($this->request->getGet('maxRecords') ?? 20), 100);
        $startPos   = max(1, (int)($this->request->getGet('startPosition') ?? 1));
        $q          = $this->request->getGet('q') ?? $this->request->getGet('ElementSetName') ?? '';

        $db  = \Config\Database::connect();
        $sql = "SELECT dataset_code, title, spatial_scope, anomaly_type, data_level,
                       is_downloadable, is_viewable, items_count
                FROM geoportal.datasets
                WHERE is_downloadable = true OR is_viewable = true";
        $binds = [];
        if ($q) {
            $sql .= " AND (title ILIKE ? OR dataset_code ILIKE ?)";
            $binds[] = "%{$q}%";
            $binds[] = "%{$q}%";
        }
        $sql .= " ORDER BY dataset_code LIMIT ? OFFSET ?";
        $binds[] = $maxRecords;
        $binds[] = $startPos - 1;

        $rows = $db->query($sql, $binds)->getResultArray();

        // Total count
        $totalRow = $db->query(
            "SELECT COUNT(*) AS cnt FROM geoportal.datasets WHERE is_downloadable = true OR is_viewable = true"
        )->getRow();
        $totalMatched = (int)($totalRow->cnt ?? 0);

        $records = '';
        $wmsBase = site_url('ogc/wms');
        $wfsBase = site_url('ogc/wfs');
        [$minx, $miny, $maxx, $maxy] = self::BBOX_JAVA;

        foreach ($rows as $row) {
            $code   = htmlspecialchars($row['dataset_code'], ENT_XML1);
            $title  = htmlspecialchars($row['title']        ?? $code, ENT_XML1);
            $scope  = htmlspecialchars($row['spatial_scope'] ?? 'Java-Bali', ENT_XML1);
            $anom   = htmlspecialchars($row['anomaly_type']  ?? '', ENT_XML1);
            $level  = (int)($row['data_level'] ?? 1);
            $count  = (int)($row['items_count'] ?? 0);

            $records .= <<<XML
  <csw:Record>
    <dc:identifier xmlns:dc="http://purl.org/dc/elements/1.1/">{$code}</dc:identifier>
    <dc:title xmlns:dc="http://purl.org/dc/elements/1.1/">{$title}</dc:title>
    <dc:type xmlns:dc="http://purl.org/dc/elements/1.1/">dataset</dc:type>
    <dc:subject xmlns:dc="http://purl.org/dc/elements/1.1/">gravity</dc:subject>
    <dc:subject xmlns:dc="http://purl.org/dc/elements/1.1/">{$anom}</dc:subject>
    <dc:subject xmlns:dc="http://purl.org/dc/elements/1.1/">geophysics</dc:subject>
    <dc:subject xmlns:dc="http://purl.org/dc/elements/1.1/">{$scope}</dc:subject>
    <dc:format xmlns:dc="http://purl.org/dc/elements/1.1/">Level {$level} — {$count} records</dc:format>
    <dc:source xmlns:dc="http://purl.org/dc/elements/1.1/">GravPort — gravport.id</dc:source>
    <dc:language xmlns:dc="http://purl.org/dc/elements/1.1/">id</dc:language>
    <ows:BoundingBox xmlns:ows="http://www.opengis.net/ows" crs="EPSG:4326">
      <ows:LowerCorner>{$minx} {$miny}</ows:LowerCorner>
      <ows:UpperCorner>{$maxx} {$maxy}</ows:UpperCorner>
    </ows:BoundingBox>
    <dct:references xmlns:dct="http://purl.org/dc/terms/" scheme="OGC:WMS">{$wmsBase}</dct:references>
    <dct:references xmlns:dct="http://purl.org/dc/terms/" scheme="OGC:WFS">{$wfsBase}</dct:references>
  </csw:Record>
XML;
        }

        $returned = count($rows);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<csw:GetRecordsResponse version="2.0.2"
  xmlns:csw="http://www.opengis.net/cat/csw/2.0.2"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dct="http://purl.org/dc/terms/"
  xmlns:ows="http://www.opengis.net/ows">
  <csw:SearchStatus timestamp="{$this->now()}"/>
  <csw:SearchResults numberOfRecordsMatched="{$totalMatched}" numberOfRecordsReturned="{$returned}" nextRecord="0" recordSchema="http://www.opengis.net/cat/csw/2.0.2">
    {$records}
  </csw:SearchResults>
</csw:GetRecordsResponse>
XML;
        return $this->xmlResponse($xml);
    }

    private function cswGetRecordById(): ResponseInterface
    {
        $id = $this->request->getGet('Id') ?? $this->request->getGet('ID') ?? $this->request->getGet('id') ?? '';
        if (!$id) {
            return $this->xmlError('MissingParameterValue', 'Id parameter is required for GetRecordById');
        }

        $db  = \Config\Database::connect();
        $row = $db->query(
            "SELECT dataset_code, title, spatial_scope, anomaly_type, data_level, items_count
             FROM geoportal.datasets WHERE dataset_code = ?",
            [strtolower($id)]
        )->getRowArray();

        if (!$row) {
            return $this->xmlError('InvalidParameterValue', "Record not found: {$id}");
        }

        [$minx, $miny, $maxx, $maxy] = self::BBOX_JAVA;
        $code  = htmlspecialchars($row['dataset_code'], ENT_XML1);
        $title = htmlspecialchars($row['title'] ?? $code, ENT_XML1);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<csw:GetRecordByIdResponse xmlns:csw="http://www.opengis.net/cat/csw/2.0.2"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:ows="http://www.opengis.net/ows">
  <csw:Record>
    <dc:identifier>{$code}</dc:identifier>
    <dc:title>{$title}</dc:title>
    <dc:type>dataset</dc:type>
    <ows:BoundingBox crs="EPSG:4326">
      <ows:LowerCorner>{$minx} {$miny}</ows:LowerCorner>
      <ows:UpperCorner>{$maxx} {$maxy}</ows:UpperCorner>
    </ows:BoundingBox>
  </csw:Record>
</csw:GetRecordByIdResponse>
XML;
        return $this->xmlResponse($xml);
    }

    // ════════════════════════════════════════════════════════════════
    // Helpers
    // ════════════════════════════════════════════════════════════════

    /**
     * Map normalized value [0.0 – 1.0] to RGB (blue→cyan→green→yellow→red).
     */
    private function valueToRgb(float $t): array
    {
        // 5-stop gradient: blue(0.0) → cyan(0.25) → green(0.5) → yellow(0.75) → red(1.0)
        $stops = [
            [0, 0, 255],
            [0, 255, 255],
            [0, 255, 0],
            [255, 255, 0],
            [255, 0, 0],
        ];
        $n   = count($stops) - 1;
        $idx = $t * $n;
        $lo  = (int)floor($idx);
        $hi  = min($lo + 1, $n);
        $f   = $idx - $lo;

        $r = (int)($stops[$lo][0] + ($stops[$hi][0] - $stops[$lo][0]) * $f);
        $g = (int)($stops[$lo][1] + ($stops[$hi][1] - $stops[$lo][1]) * $f);
        $b = (int)($stops[$lo][2] + ($stops[$hi][2] - $stops[$lo][2]) * $f);

        return [$r, $g, $b];
    }

    private function xmlResponse(string $xml, int $status = 200): ResponseInterface
    {
        $response = service('response');
        $response->setStatusCode($status);
        $response->setContentType('application/xml; charset=utf-8');
        $response->setHeader('X-GravPort-OGC', '1.0');
        $response->setBody($xml);
        return $response;
    }

    private function xmlError(string $code, string $text): ResponseInterface
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ows:ExceptionReport xmlns:ows="http://www.opengis.net/ows" version="1.1.0" language="en">
  <ows:Exception exceptionCode="{$code}">
    <ows:ExceptionText>{$text}</ows:ExceptionText>
  </ows:Exception>
</ows:ExceptionReport>
XML;
        return $this->xmlResponse($xml, 400);
    }

    private function now(): string
    {
        return date('c');
    }
}
