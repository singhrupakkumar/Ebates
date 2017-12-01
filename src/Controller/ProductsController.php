<?php
namespace App\Controller;

use App\Controller\AppController;

use Cake\Event\Event;
use Cake\Routing\Router;
use Cake\Mailer\Email;
use Cake\Auth\DefaultPasswordHasher;
//use \CROSCON\CommissionJunction\Client;      



/**
 * Products Controller
 *
 * @property \App\Model\Table\ProductsTable $Products
 *
 * @method \App\Model\Entity\Product[] paginate($object = null, array $settings = [])
 */
class ProductsController extends AppController
{

    
    
        public function beforeFilter(Event $event) {

        parent::beforeFilter($event);



        $this->Auth->allow(['add', 'searchjson', 'search','view','index']);     

        $this->authcontent(); 
    } 
    
    
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {

        include(ROOT.'/croscon/commissionjunction-php/lib/CROSCON/CommissionJunction/Client.php');
        include(ROOT.'/croscon/commissionjunction-php/lib/CROSCON/CommissionJunction/Exception.php'); 
        $parameters = array('4961822','USD','1000','500','','','','8507402');
        
        $devKey = '008d0d656cc5e9dd96fd4abd6ad9b9bb6d4ce0d8deb5d2d386cd35bb8932f77a6486a4b9d70acfba1035e8d42a55d83ca3c906a3ee48cff8a9c9e047d6379b649f/32b010b5a0fda4bac0b51e128ac15ae031534fbbe0bd2d7470c4116b815c3ba7e0dfe1aab82f8c476eae5f00e522a97728fc94d59f577f86327476c30c129fe9';
$siteKey = '8507402'; // the siteID for the site you are running the script on
$advertiserIds = '4441042,4441042'; // e.g., I think these are Buy.com USA and CA and SHOP.com - the script will only search on these advertisers, since you don't want to market products from advertisers you don't have a relationship with, right?
$currency = 'USD';
$ParamArray = array('developerKey' => $devKey, 'websiteId' => $siteKey, 'advertiserIds' => $advertiserIds, 'currency' => $currency); // parameters I will include in all my calls

        $client->productSearch($ParamArray);       
        echo "<pre>";
        print_r($client);
        exit;
        
//        $this->paginate = [
//            'contain' => ['Cats', 'Stores']
//        ];
//        $products = $this->paginate($this->Products);
//
//        $this->set(compact('products'));
//        $this->set('_serialize', ['products']);
    }

    /**
     * View method
     *
     * @param string|null $id Product id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
    
        $product = $this->Products->find('all', array('contain'=>array('Stores'),
                'conditions' => ['Products.id'=>$id],
                'limit' => 200,
            ));
        $product = $product->first(); 
        $this->set('product', $product);
        $this->set('_serialize', ['product']);
    }

     public function searchjson() {
        $term = null;
        if(!empty($this->request->query['term'])) {
            $term = $this->request->query['term'];
            $terms = explode(' ', trim($term));
            $terms = array_diff($terms, array(''));
            $conditions = array(
                // 'Brand.active' => 1,
                'Products.status' => 1
            );
            foreach($terms as $term) {
                $conditions[] = array('Products.name LIKE' => '%' . $term . '%');
            }
            $products = $this->Products->find('all', array(
                'recursive' => -1,
                'contain' => array(
                     'Stores'
                ),
                'fields' => array(
                    'Products.id',
                    'Products.name',
                    'Products.image'
                ),
                'conditions' => $conditions,
                'limit' => 20,
            ));
        }
        
         $products = $products->all(); 
          $products = $products->toArray();
        
        echo json_encode($products);
        exit;

    }
    
    
    public function search() { 
        $search = null;
        if(!empty($this->request->query['search']) || !empty($this->request->data['name'])) {
            $search = empty($this->request->query['search']) ? $this->request->data['name'] : $this->request->query['search'];
            $search = preg_replace('/[^a-zA-Z0-9 ]/', '', $search);
            $terms = explode(' ', trim($search));
            $terms = array_diff($terms, array(''));
            $conditions = array(
                'Products.status' => 1
            );
            foreach($terms as $term) {
                $terms1[] = preg_replace('/[^a-zA-Z0-9]/', '', $term);
                $conditions[] = array('Products.name LIKE' => '%' . $term . '%');
            }
            $products = $this->Products->find('all', array(
                'recursive' => -1,
                'contain' => array(
                    'Stores'
                ),
                'conditions' => $conditions,
                'limit' => 200,
            ));
           
            
             $products = $products->all(); 
             $products = $products->toArray();

            if(count($products) == 1) {
                return $this->redirect(array('controller' => 'products', 'action' => 'view/'.$products[0]['id']));
            }
            $terms1 = array_diff($terms1, array(''));
            $this->set(compact('products', 'terms1'));
        }
        $this->set(compact('search'));

        if ($this->request->is('ajax')) {
            $this->layout = false;
            $this->set('ajax', 1);
        } else {
            $this->set('ajax', 0);
        }

        $this->set('title_for_layout', 'Search');

        $description = 'Search';
        $this->set(compact('description'));

        $keywords = 'search';
        $this->set(compact('keywords'));
    }
    
    
    
    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $product = $this->Products->newEntity();
        if ($this->request->is('post')) {
            $product = $this->Products->patchEntity($product, $this->request->getData());
            if ($this->Products->save($product)) {
                $this->Flash->success(__('The product has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The product could not be saved. Please, try again.'));
        }
        $cats = $this->Products->Cats->find('list', ['limit' => 200]);
        $stores = $this->Products->Stores->find('list', ['limit' => 200]);
        $this->set(compact('product', 'cats', 'stores'));
        $this->set('_serialize', ['product']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Product id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $product = $this->Products->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $product = $this->Products->patchEntity($product, $this->request->getData());
            if ($this->Products->save($product)) {
                $this->Flash->success(__('The product has been saved.'));  

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The product could not be saved. Please, try again.'));
        }
        $cats = $this->Products->Cats->find('list', ['limit' => 200]);
        $stores = $this->Products->Stores->find('list', ['limit' => 200]);
        $this->set(compact('product', 'cats', 'stores'));
        $this->set('_serialize', ['product']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Product id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $product = $this->Products->get($id);
        if ($this->Products->delete($product)) {
            $this->Flash->success(__('The product has been deleted.'));
        } else {
            $this->Flash->error(__('The product could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
