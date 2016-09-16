Changelog
=====

31-JUL-2016 8.x-1.0-alpha1

Status
====

The 8.x-1.x version is a ground up rewrite of a module originally written for
Drupal 5.

The following feature sets are considered critical for each release stage. Items
marked with [x] are considered complete.

# Alpha
- [x] Domain entity for storage and configuration
- [x] Domain negotiation for active requests
- [x] API for modifying active domain requests
- [x] API for modifying paths per domain
- [x] Mechanism for access restriction inactive domains
- [x] Form for adding / editing / deleting domain records
- [x] Validation of domain record patterns
- [x] Domain server information block
- [x] Domain switcher block
- [x] Domain context for block visibility
- [x] Entity reference support, with an API for altering entity lists
- [x] Alias registration and negotiation
- [x] Alias validation
- [x] Configuration overrides per domain
- [x] Language-aware configuration overrides
- [x] Enable node access control via domain references (Domain Access)
- [x] Allow nodes to be assigned to domains
- [x] Allow users to be assigned as domain editors
- [x] Set permissions for content editing based on domain
- [x] Restrict publication options based on user domains
- [x] Support the all affiliates concept of publication
- [x] Support the all affiliates concept for editors
- [x] Test and document cross-domain logins
- [x] Make language optional for domain overrides
- [x] Use domain source for path rewrites
- [x] Finish the path alter behavior
- [x] Catalog @TODO items as issues
- [x] Drupal cache system makes it difficult to serve different homepage for each domain
- [x] Provide common views support for attached fields
- [x] Recreate the Domain Content module with bulk operations
- [x] Add help text to domain overview screen
- [x] Views argument handler to show proper title.
- [x] Ensure unique numeric ids for use with node access
- [x] Invalidate cache on Domain save
- [x] Invalidate render cache on Alias save
- [x] DomainConfigOverrider returns empty $overrides
- [x] Allow non-ascii domains and aliases
- [x] The domain_validate hook needs tests
- [x] Allow partial config loading

# Beta
- [x] Actions for domain operations
- [x] Drush support for domain operations
- [x] Replace / inject the storage manager in DomainAliasLoader.
- [x] Replace / inject the storage manager in DomainLoader.
- [ ] Write tests for Domain Content.
- [x] Views access handler for domain content.
- [ ] Restrict Domain Source options using JS
- [ ] Recreate the Domain Theme module
- [ ] Advanced drush integration / complete labelled tasks
- [ ] Check domain responses on configuration forms
- [x] Handle site name overrides -- perhaps as a new field?
- [x] Restore the `domain_grant_all` permission?
- [x] Domain token support
- [ ] Test cron handling
- [x] Module configurations
  - [x] Allow configuration of access-exempt paths for inactive domains
  - [x] www prefix handling
  - [x] Add domain-specific CSS classes
  - [x] Path matching for URL rewrites?
  - [x] Allow non-ascii characters in domains
- [ ] Recreate the Domain Nav module
- [ ] Support Tour module
- [ ] Allow selective access to domain record editing
- [ ] Allow access to actions based on assigned domain permissions
- [ ] Implement theme functions or twig templates where proper
- [ ] Tests for all module hooks
- [x] Proper tests for domain record validation
- [x] Check test logic in testDomainAliasNegotiator()
- [x] Test that sort logic in DomainAliasLoader matches what is documented
- [ ] Error handling in DomainAliasForm
- [ ] Error checking in DomainAliasController
- [ ] Deprecated methods in DomainAliasController
- [ ] Error reporting in `domain_alias_domain_request_alter()`
- [ ] Ensure completeness of DomainAccessPermissionsTest
- [ ] Check module setup behavior in tests
- [ ] Make all affiliates default value configurable?
- [ ] Cache in the DomainAccessManager
- [ ] Remove deprecated `entity_get_form_display`
- [ ] Review drupalUserIsLoggedIn() hack
- [x] Review DomainNegotiatorTest for completeness
- [x] Review core note in DomainEntityReferenceTest
- [ ] Expand DomainActionsTest
- [ ] DomainViewBuilder review
- [ ] Dependency Injection in DomainValidator
- [ ] Caching strategies in DomainNegotiator
- [ ] Caching strategies in DomainConfigOverrides
- [ ] Inject the module handler service in DomainListBuilder::getOperations()
- [ ] `drush_domain_generate_domains()` has odd counting logic
- [ ] Separate permissions for Domain Alias
- [ ] Check loader logic in the DomainSource PathProcessor
- [ ] Check loader logic in Domain Access node_access
- [ ] Check id logic in Domain Alias list controller

# Final
- [ ] Security review
- [ ] Provide an upgrade path from 6.x
- [ ] Provide an upgrade path from 7.x-3.x
- [ ] Remove calls to deprecated methods / classes
- [ ] Remove unnecessary use statements
