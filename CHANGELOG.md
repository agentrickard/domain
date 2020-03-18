Changelog
=====

31-JUL-2016 8.x-1.0-alpha1
22-AUG-2016 8.x-1.0-alpha2
30-AUG-2016 8.x-1.0-alpha3
01-SEP-2016 8.x-1.0-alpha4
25-OCT-2016 8.x-1.0-alpha5
20-NOV-2016 8.x-1.0-alpha6
06-DEC-2016 Adds the domain_alpha module to handle critical pre-release updates.
13-DEC-2016 8.x-1.0-alpha7
12-MAR-2017 8.x-1.0-alpha8
23-APR-2017 8.x-1.0-alpha9
01-DEC-2017 8.x-1.0-alpha10
19-DEC-2017 8.x-1.0-alpha11
12-FEB-2018 8.x-1.0-alpha12
07-MAR-2018 8.x-1.0-alpha13
19-OCT-2018 8.x-1.0-alpha14
21-FEB-2019 8.x-1.0-alpha15
21-JUN-2019 8.x-1.0-alpha16

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
- [x] Replace / inject the storage manager in domainStorage.
- [x] Write tests for Domain Content.
- [x] Views access handler for domain content.
- [x] Restrict Domain Source options using JS
- [x] Handle site name overrides -- perhaps as a new field?
- [x] Restore the `domain_grant_all` permission?
- [x] Domain token support
- [x] Module configurations
  - [x] Allow configuration of access-exempt paths for inactive domains
  - [x] www prefix handling
  - [x] Add domain-specific CSS classes
  - [x] Path matching for URL rewrites?
  - [x] Allow non-ascii characters in domains
- [x] Recreate the Domain Nav module
- [x] Allow selective access to domain record editing
- [x] Allow access to actions based on assigned domain permissions
- [x] Tests for all module hooks
- [x] Proper tests for domain record validation
- [x] Check test logic in testDomainAliasNegotiator()
- [x] Test that sort logic in DomainAliasLoader matches what is documented
- [x] Error handling in DomainAliasForm
- [x] Error checking in DomainAliasController
- [x] Deprecated methods in DomainAliasController
- [x] Error reporting in `domain_alias_domain_request_alter()`
- [x] Ensure completeness of DomainAccessPermissionsTest
- [x] Check module setup behavior in tests
- [x] Make all affiliates default value configurable
- [x] Review drupalUserIsLoggedIn() hack
- [x] Review DomainNegotiatorTest for completeness
- [x] Review core note in DomainEntityReferenceTest
- [x] Expand DomainActionsTest
- [x] DomainViewBuilder review
- [x] Dependency Injection in DomainValidator
- [x] Inject the module handler service in DomainListBuilder::getOperations()
- [x] `drush_domain_generate_domains()` has odd counting logic
- [x] Separate permissions for Domain Alias
- [x] Check loader logic in the DomainSource PathProcessor
- [x] Check loader logic in Domain Access `node_access`
- [x] Check id logic in Domain Alias list controller
- [x] Check domain responses on configuration forms
- [x] Remove deprecated `entity_get_form_display`
- [x] Implement theme functions or twig templates where proper
- [x] Advanced drush integration / complete labelled tasks
- [ ] Add filter options to domain_access and domain_source views
- [ ] Test cron handling
- [ ] Caching strategies in DomainNegotiator
- [ ] Caching strategies in DomainConfigOverrides
- [ ] Cache in the DomainAccessManager
- [ ] Proper handling of default node values
- [ ] Do not allow actions to be edited?
- [o] Recreate the Domain Theme module -- see https://www.drupal.org/project/domain_theme_switch

# Final
- [ ] Security review
- [ ] Provide an upgrade path from 6.x
- [ ] Provide an upgrade path from 7.x-3.x
- [x] Remove calls to deprecated methods / classes
- [ ] Remove unnecessary use statements
- [ ] Support Tour module
- [ ] Views schema fails -- see https://www.drupal.org/project/drupal/issues/2834801
