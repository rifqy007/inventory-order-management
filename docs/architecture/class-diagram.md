# Class Diagram As-Built

Diagram ini menggambarkan dependency yang terlihat pada implementasi saat ini. Label `interface` menandai dependency ke kontrak; label `concrete` menandai dependency ke class implementasi.

```mermaid
classDiagram
    class FrontController {
        <<front controller>>
    }
    class AuthController
    class DashboardController
    class MasterController
    class AdminUserController
    class InventoryController
    class OrderController
    class WorkflowController
    class AuthService
    class InventoryService
    class OrderService
    class SalesOrder {
        <<entity>>
        +transitionTo(nextStatus) SalesOrder
    }
    class InventoryRepositoryInterface {
        <<interface>>
    }
    class OrderRepositoryInterface {
        <<interface>>
    }
    class MysqlInventoryRepository
    class FakeInventoryRepository
    class MysqlOrderRepository
    class QueryBuilder
    class PDO

    FrontController --> AuthController : concrete
    FrontController --> DashboardController : concrete
    FrontController --> MasterController : concrete
    FrontController --> AdminUserController : concrete
    FrontController --> InventoryController : concrete
    FrontController --> OrderController : concrete
    FrontController --> WorkflowController : concrete
    FrontController --> MysqlInventoryRepository : concrete API query

    AuthController --> AuthService : concrete
    AuthService --> InventoryRepositoryInterface : interface
    DashboardController --> InventoryRepositoryInterface : interface
    InventoryService --> InventoryRepositoryInterface : interface
    OrderService --> OrderRepositoryInterface : interface
    OrderService --> SalesOrder : lifecycle rules

    MasterController --> MysqlInventoryRepository : concrete
    InventoryController --> InventoryService : concrete
    InventoryController --> MysqlInventoryRepository : concrete
    OrderController --> MysqlInventoryRepository : concrete
    OrderController --> PDO : concrete
    AdminUserController --> PDO : concrete
    WorkflowController --> OrderService : concrete
    WorkflowController --> OrderRepositoryInterface : interface
    WorkflowController --> MysqlInventoryRepository : concrete

    MysqlInventoryRepository ..|> InventoryRepositoryInterface : implements
    FakeInventoryRepository ..|> InventoryRepositoryInterface : implements
    MysqlOrderRepository ..|> OrderRepositoryInterface : implements
    MysqlInventoryRepository --> QueryBuilder : concrete
    QueryBuilder --> PDO : concrete
    MysqlInventoryRepository --> PDO : concrete
    MysqlOrderRepository --> PDO : concrete
```

Rancangan awal membayangkan akses data controller melewati service dan interface repository. Implementasi as-built memakai interface untuk service auth, dashboard, inventori, dan order, tetapi beberapa controller serta endpoint API masih bergantung pada repository MySQL atau PDO secara langsung. Perubahan lifecycle Sales Order juga dipusatkan pada entity `SalesOrder`; dependency konkret yang tersisa dicatat di `docs/quality/tech-debt.md`.
