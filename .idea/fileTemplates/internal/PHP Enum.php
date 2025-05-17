<?php
declare(strict_types=1);

#parse("PHP File Header.php")

#if (${NAMESPACE})
namespace ${NAMESPACE};

#end
/**
 * ${NAME}
 */
enum ${NAME}#if (${BACKED_TYPE}) : ${BACKED_TYPE} #end{

}

