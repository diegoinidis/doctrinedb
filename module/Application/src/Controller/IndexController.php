<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Laminas\Paginator\Paginator;
use Application\Entity\Post;
use Application\Form\PostForm;

class IndexController extends AbstractActionController
{
   /**
   * Entity manager.
   * @var Doctrine\ORM\EntityManager
   */
  private $entityManager;

  private $postManager;

  // Constructor method is used to inject dependencies to the controller.
  public function __construct($entityManager, $postManager) 
  {
      $this->entityManager = $entityManager;
      $this->postManager = $postManager;
  }
  

  // This is the default "index" action of the controller. It displays the
  // Posts page containing the recent blog posts.
  public function indexAction()
  {
    $page = $this->params()->fromQuery('page', 1);
    $tagFilter = $this->params()->fromQuery('tag', null);
    
    if ($tagFilter) {
     
        // Filter posts by tag
        $query = $this->entityManager->getRepository(Post::class)
                ->findPostsByTag($tagFilter);
        
    } else {
        // Get recent posts
        $query = $this->entityManager->getRepository(Post::class)
                ->findPublishedPosts();
    }
    
    $adapter = new DoctrineAdapter(new ORMPaginator($query, false));
    $paginator = new Paginator($adapter);
    $paginator->setDefaultItemCountPerPage(10);        
    $paginator->setCurrentPageNumber($page);
                   
    // Get popular tags.
    $tagCloud = $this->postManager->getTagCloud();
    
    // Render the view template.
    return new ViewModel([
        'posts' => $paginator,
        'postManager' => $this->postManager,
        'tagCloud' => $tagCloud
    ]);
  }

  public function addAction()
  {
    $form = new PostForm();

    if ($this->getRequest()->isPost())
    {
      $data = $this->params()->fromPost();

      $form->setData($data);
      if ($form->isValid())
      {
        $data = $form->getData();

        $this->postManager->addNewPost($data);

        return $this->redirect()->toRoute('application');
      }
    }

    return new ViewModel([
      'form'  => $form
    ]);
  }
}
