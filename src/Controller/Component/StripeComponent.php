<?php
namespace CakephpStripe\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;

/**
 * Stripe Component
 *
 */
class StripeComponent extends Component
{
    /**
     * Stripe mode, can be 'Live' or 'Test'
     *
     * @var string
     */
    public $mode = 'Test';
    
    
    /**
     * Stripe API Secret key
     *
     * @var string
     */
    public $key = null;
    
    
    /**
     * Stripe currency, default is 'usd'
     *
     * @var string
     */
    public $currency = 'usd';
    
    
    /**
     *
     * If provided, statuses will be saved in that file, default is false
     * if enabled, log should be added in bootstrap, e.g. if log file should be in tmp/logs/stripe.log
     *
     * @var string
     */
    public $logFile = false;
    
    
    /**
     * Can be 'both', 'success' or 'error', to what results to save, default is 'error'
     *
     * @var string
     */
    public $logType = 'error';
    

    /**
     * @property \App\Controller\AppController
     */
    public $controller = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        
        // set controller
        $this->controller = $this->_registry->getController();
        
        // if mode is not set in bootstrap, defaults to 'Test'
        $mode = Configure::read('Stripe.mode');
        if ($mode) {
            $this->mode = $mode;
        }
        
        // set the Stripe API key
        $this->key = Configure::read('Stripe.' . $this->mode . 'Secret');
        if (!$this->key) {
            throw new Exception('Stripe API Secret key is not set');
        }
        
        // if currency is not set, defaults to 'usd'
        $currency = Configure::read('Stripe.currency');
        if ($currency) {
            $this->currency = strtolower($currency);
        }
    }
    
    
    
    
    
    
    
}
