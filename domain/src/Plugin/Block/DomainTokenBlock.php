<?php

namespace Drupal\domain\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;

/**
 * Provides a token information block for a domain request.
 *
 * @Block(
 *   id = "domain_token_block",
 *   admin_label = @Translation("Domain token information")
 * )
 */
class DomainTokenBlock extends DomainBlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = AccessResult::allowedIfHasPermissions($account, array('administer domains', 'view domain information'), 'OR');
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Build the output.
   */
  public function build() {
    /** @var \Drupal\domain\DomainInterface $domain */
    $domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    if (!$domain) {
      return array(
        '#markup' => $this->t('No domain record could be loaded.'),
      );
    }
    $header = array($this->t('Token'), $this->t('Value'));
    return array(
      '#theme' => 'table',
      '#rows' => $this->renderTokens($domain),
      '#header' => $header,
    );
  }

  /**
   * Generates available tokens for printing.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The active domain request.
   * @return array
   *   An array keyed by token name, with value of replacement value.
   */
  private function renderTokens(DomainInterface $domain) {
    $rows = array();
    $token = \Drupal::token();
    $tokens = $token->getInfo();
    // The 'domain' token is supported by core. The others by Token module,
    // so we cannot assume that Token module is present.
    $domain_tokens = ['domain', 'current-domain', 'default-domain'];
    foreach ($domain_tokens as $key) {
      if (isset($tokens['tokens'][$key])) {
        $data = [];
        // Pass domain data to the default handler.
        if ($key == 'domain') {
          $data['domain'] = $domain;
        }
        foreach ($tokens['tokens'][$key] as $name => $info) {
          $string = "[$key:$name]";
          $rows[] = [
            $string,
            $token->replace($string, $data),
          ];
        }
      }
    }
    return $rows;
  }

}
