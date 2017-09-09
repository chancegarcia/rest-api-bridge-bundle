# general
- config settings
- exception listener/controller
# controller
- use config settings to enable serialize null
- use config settings to enable max depth
# handler

# format handling
if FOSrest formatt listener rules aren't defined, we have issues with cget and get returns. probably need to simplify configuration for multiple bundles as shown here: https://symfony.com/doc/current/bundles/prepend_extension.html

    fos_rest:
        param_fetcher_listener: true
        serializer:
            serialize_null: true
        view:
            view_response_listener: 'force'
            formats:
                xml:  true
                json: true
            templating_formats:
                html: true
                json: false
        format_listener:
            rules:
                - { path: ^/api, priorities: [], fallback_format: 'json', prefer_extension: false }
                - { path: ^/, priorities: [ 'html', 'json', 'xml' ], fallback_format: ~, prefer_extension: false }
        exception:
            codes:
                'Symfony\Component\Routing\Exception\ResourceNotFoundException': 404
                'Doctrine\ORM\OptimisticLockException': 'HTTP_CONFLICT'
            messages:
                'Symfony\Component\Routing\Exception\ResourceNotFoundException': true
        allowed_methods_listener: true
        access_denied_listener:
            json: true
        body_listener: true
    #    disable_csrf_role: ROLE_API
        disable_csrf_role: 'IS_AUTHENTICATED_ANONYMOUSLY'
        routing_loader:
            default_format: 'json'
            include_format: false 