<option{+START,IF,{SELECTED}} selected="selected"{+END}{+START,IF,{DISABLED}} disabled="disabled"{+END} value="{NAME*}"{+START,IF_NON_EMPTY,{CLASS}} class="{CLASS*}"{+END}{+START,IF_PASSED,TITLE} title="{TITLE*}"{+END}>{$STRIP_TAGS,{TEXT*}}</option>

