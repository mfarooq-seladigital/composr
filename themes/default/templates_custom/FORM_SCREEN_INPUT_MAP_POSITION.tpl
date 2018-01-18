{$REQUIRE_JAVASCRIPT,data_mappr}
{$SET,google_map_key,{$CONFIG_OPTION,google_map_key}}
<div data-require-javascript="data_mappr" data-tpl="formScreenInputMapPosition" data-tpl-params="{+START,PARAMS_JSON,LATITUDE,LONGITUDE,NAME,google_map_key}{_*}{+END}">
	<div id="map_position_{NAME*}" style="width:100%; height:300px"></div>

	<label for="{NAME*}_latitude">
		{!LATITUDE}
		<input class="js-change-set-place-marker {+START,IF,{REQUIRED}}hidden-required{+END}" type="number" step="any" id="{NAME*}_latitude" name="latitude" value="{LATITUDE*}" />
	</label>

	<label for="{NAME*}_longitude">
		{!LONGITUDE}
		<input class="js-change-set-place-marker {+START,IF,{REQUIRED}}hidden-required{+END}" type="number" step="any" id="{NAME*}_longitude" name="longitude" value="{LONGITUDE*}" />
	</label>

	<input class="button-micro buttons--search js-click-geolocate-user-for-map-field" data-click-pd type="button" value="{!FIND_ME}" />
</div>
