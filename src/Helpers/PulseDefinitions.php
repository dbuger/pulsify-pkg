<?php

define("PS_WHERE_LIKE","->where('{{field}}','like', '%'. {{search}} .'%')");
define("PS_OR_WHERE_LIKE","->orWhere('{{field}}','like', '%'. {{search}} .'%')");
define("PS_WHERE_EQUAL","->where('{{field}}','=',{{search}})");
define("PS_OR_WHERE_EQUAL","->orWhere('{{field}}','=',{{search}})");
define("PS_WHERE_BETWEEN","->whereBetween('{{field}}',[{{search}}])");
define("PS_OR_WHERE_BETWEEN","->orWhereBetween('{{field}}',[{{search}}])");
define("PS_WHERE_RAW","->whereRaw('{{field}}'");
define("PS_OR_WHERE_RAW","->orWhereRaw('{{field}}'");
define("PS_WHERE_HAS",'->whereHas(\'{{field}}\', function(${{field}}){${{field}}{{subQuery}};})');
define("PS_OR_WHERE_HAS",'->orWhereHas(\'{{field}}\', function(${{field}}){${{field}}{{subQuery}};})');
define("PS_SEARCHABLE",'function($query){$query{{generatedQuery}}}');

define("PS_BELONGS_TO_MANY_SAVABLE_RELATION",1);
define("PS_HAS_MANY_SAVABLE_RELATION",2);
define("PS_HAS_ONE_SAVABLE_RELATION",3);


define("PS_MIGRATION_STRING_DEFAULT_EMPTY",'$table->string("{{field}}")->default("");');
define("PS_MIGRATION_STRING",'$table->string("{{field}}");');
define("PS_MIGRATION_INTEGER_DEFAULT_NEGATIVE",'$table->integer("{{field}}")->default(-1);');
define("PS_MIGRATION_INTEGER",'$table->integer("{{field}}");');
define("PS_MIGRATION_DATE_TIME",'$table->dateTime("{{field}}");');
define("PS_MIGRATION_DATE",'$table->date("{{field}}");');
define("PS_MIGRATION_TIMESTAMP",'$table->timestamp("{{field}}");');