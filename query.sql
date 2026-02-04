CREATE TABLE GoodsCategory(
    CategoryID INT PRIMARY KEY,
    CategoryName VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE Maker(
    MakerID INT PRIMARY KEY,
    MakerName VARCHAR(30) NOT NULL UNIQUE,
    MakerURL VARCHAR(50)
);

CREATE TABLE Customer(
    CustomerID INT PRIMARY KEY,
    CustomerName VARCHAR(30) NOT NULL,
    Address VARCHAR(255) NOT NULL,
    Tel VARCHAR(20),
    Email VARCHAR(100) NOT NULL UNIQUE,
    CardNumber VARCHAR(20),
    Password VARCHAR(10) NOT NULL
);

CREATE TABLE Goods(
    GoodsID INT PRIMARY KEY,
    CategoryID INT,
    GoodsName VARCHAR(40) NOT NULL UNIQUE,
    PRICE INT,
    CostPrice INT NOT NULL,
    MakerID INT NOT NULL,
    Stock INT NOT NULL DEFAULT 0,
    ImageName VARCHAR(60),
    FOREIGN KEY (CategoryID) REFERENCES GoodsCategory(CategoryID),
    FOREIGN KEY (MakerID) REFERENCES Maker(MakerID)
);

CREATE TABLE GoodsOrder(
    OrderID INT PRIMARY KEY,
    CustomerID INT NOT NULL,
    OrderDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Pay INT NOT NULL,
    FOREIGN KEY (CustomerID) REFERENCES Customer(CustomerID),
    CHECK (Pay IN (1, 2, 3))
);


CREATE TABLE OrderDetail(
    OrderID INT,
    GoodsID INT,
    OrderAmount INT NOT NULL,
    PRIMARY KEY(OrderID, GoodsID),
    FOREIGN KEY (OrderID) REFERENCES GoodsOrder(OrderID),
    FOREIGN KEY (GoodsID) REFERENCES Goods(GoodsID),
    CHECK (OrderAmount > 0)
);

CREATE TABLE ShoppingCart(
    SessionID VARCHAR(32),
    GoodsID INT,
    CartAmount INT NOT NULL,
    PRIMARY KEY(SessionID, GoodsID),
    FOREIGN KEY (GoodsID) REFERENCES Goods(GoodsID)
);


insert into GoodsCategory values (1, 'CPU');

insert into GoodsCategory values (2, 'メモリ');

insert into GoodsCategory values (3, 'ハードディスク');

insert into GoodsCategory values (4, 'ビデオカード');

INSERT INTO Maker VALUES ( 1, 'Intel', 'http://www.intel.co.jp' );

INSERT INTO Maker VALUES ( 2, 'AMD', 'http://www3pub.amd.com/japan/' );

INSERT INTO Maker VALUES (3, 'Cyrix', null);

INSERT INTO Maker VALUES ( 4, 'AOpen', 'http://www.aopen.co.jp/' );

INSERT INTO Maker VALUES ( 5, 'MELCO', 'http://buffalo.melcoinc.co.jp' );

INSERT INTO Maker VALUES (6, 'PRINSTON', null);

INSERT INTO Maker VALUES (7, 'ノーブランド', null);

INSERT INTO Maker VALUES ( 8, 'IBM', 'http://www.ibm.com/jp/' );

INSERT INTO Maker VALUES ( 9, 'MAXTOR', 'http://www.maxtor.co.jp/' );

INSERT INTO Maker VALUES ( 10, 'Quantum', 'http://www.quantum.co.jp/' );

INSERT INTO
    Maker
VALUES (
        11,
        'Seagate',
        'http://www.seagate-asia.com/sgt/japan'
    );

INSERT INTO Maker VALUES (12, 'WesternDigital', null);

INSERT INTO Maker VALUES (13, '3Dlabs', null);

INSERT INTO Maker VALUES ( 14, 'ASUSTeK', 'http://www.asus.co.jp/' );

INSERT INTO Maker VALUES (15, 'ATI', null);

INSERT INTO
    Maker
VALUES (
        16,
        'CANOPUS',
        'http://www.canopus.co.jp/index_j.htm'
    );

INSERT INTO Maker VALUES ( 17, 'Creative', 'http://japan.creative.com/' );

INSERT INTO Maker VALUES (18, 'DIAMOND', null);

INSERT INTO Maker VALUES ( 19, 'Leadtek', 'http://www.leadtek.co.jp/' );
