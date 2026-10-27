<?php







if ( ! defined( 'ABSPATH' ) ) {
    exit;
}












function wordpress_code_ai_is_mcp_request(): bool {
    $is_mcp_request = defined( 'WORDPRESS_CODE_AI_MCP_REQUEST' ) && WORDPRESS_CODE_AI_MCP_REQUEST;

    




    return (bool) apply_filters( 'wordpress_code_ai_is_mcp_request', $is_mcp_request );
}










function wordpress_code_ai_is_tool_call(): bool {
    
    
    $tool_name = class_exists( '\\wordpress_code_ai\\MCP\\Tool_Call_Stack', false ) ? \wordpress_code_ai\MCP\Tool_Call_Stack::current() : null;

    





    return (bool) apply_filters( 'wordpress_code_ai_is_tool_call', null !== $tool_name, $tool_name );
}
